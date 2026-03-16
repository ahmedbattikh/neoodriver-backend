<?php
declare(strict_types=1);

namespace App\Service;

use App\Entity\Driver;
use App\Entity\DriverIntegration;
use App\Entity\DriverIntegrationAccount;
use App\Entity\IntegrationSyncLog;
use App\Entity\PaymentOperation;
use App\Enum\PaymentMethodType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

final class IntegrationSyncService
{
    private array $tokenCache = [];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly BoltService $bolt,
        private readonly ?LoggerInterface $logger = null,
    ) {}

    public function runForWindow(
        \DateTimeImmutable $start,
        \DateTimeImmutable $end,
        int $hours,
        string $triggerType = 'command',
        ?int $retryOfLogId = null,
    ): IntegrationSyncLog {
        $log = new IntegrationSyncLog();
        $log->setStatus('RUNNING');
        $log->setStartAt($start);
        $log->setEndAt($end);
        $log->setHours($hours);
        $log->setStartedAt(new \DateTimeImmutable('now'));
        $log->setTriggerType($triggerType);
        $log->setRetryOfLogId($retryOfLogId);
        $this->em->persist($log);
        $this->em->flush();

        try {
            $result = $this->sync($start->getTimestamp(), $end->getTimestamp());
            $log->setStatus('SUCCESS');
            $log->setFinishedAt(new \DateTimeImmutable('now'));
            $log->setTotalOps($result['totalOps']);
            $log->setSyncedAccounts($result['syncedAccounts']);
            $log->setAccountsTotal($result['accountsTotal']);
            $log->setIntegrationsTotal($result['integrationsTotal']);
            $log->setReport($result['report']);
            $log->setErrorMessage(null);
        } catch (\Throwable $e) {
            $log->setStatus('FAILED');
            $log->setFinishedAt(new \DateTimeImmutable('now'));
            $log->setErrorMessage($e->getMessage());
            if ($this->logger) {
                $this->logger->error('integration_sync_failed', [
                    'error' => $e->getMessage(),
                    'start_at' => $start->format(\DateTimeInterface::ATOM),
                    'end_at' => $end->format(\DateTimeInterface::ATOM),
                    'trigger' => $triggerType,
                    'retry_of_log_id' => $retryOfLogId,
                ]);
            }
        }

        $this->em->flush();
        return $log;
    }

    private function sync(int $startTs, int $endTs): array
    {
        $accounts = $this->em->getRepository(DriverIntegrationAccount::class)->findAll();
        if (!$accounts) {
            return [
                'totalOps' => 0,
                'syncedAccounts' => 0,
                'accountsTotal' => 0,
                'integrationsTotal' => 0,
                'report' => [],
            ];
        }

        $accountsByIntegration = [];
        foreach ($accounts as $acc) {
            if (!$acc instanceof DriverIntegrationAccount) {
                continue;
            }
            $integration = $acc->getIntegration();
            if (!$integration instanceof DriverIntegration || !$integration->isEnabled()) {
                continue;
            }
            $key = (string) ($integration->getId() ?? $integration->getCode());
            if (!isset($accountsByIntegration[$key])) {
                $accountsByIntegration[$key] = [
                    'integration' => $integration,
                    'accounts' => [],
                ];
            }
            $accountsByIntegration[$key]['accounts'][] = $acc;
        }

        $totalOps = 0;
        $syncedAccounts = 0;
        $report = [];

        foreach ($accountsByIntegration as $group) {
            $integration = $group['integration'];
            $accountsGroup = $group['accounts'];
            if (!$integration instanceof DriverIntegration) {
                continue;
            }
            $accessToken = $this->getAccessToken($integration);
            if ($accessToken === '') {
                $report[] = [
                    'integrationCode' => $integration->getCode(),
                    'accounts' => count($accountsGroup),
                    'orders' => 0,
                    'processed' => 0,
                    'error' => 'missing_access_token',
                ];
                continue;
            }

            $companyIds = $integration->getBoltCompanyIds();
            $ordersPayload = $this->bolt->getFleetOrders($accessToken, 0, 1000, $companyIds, $startTs, $endTs, 'price_review');
            $orders = $this->extractOrders($ordersPayload);
            $ordersByDriver = $this->groupOrdersByDriver($orders);

            $processedForIntegration = 0;
            foreach ($accountsGroup as $acc) {
                if (!$acc instanceof DriverIntegrationAccount) {
                    continue;
                }
                $externalRef = strtolower(trim($acc->getIdDriver()));
                if ($externalRef === '') {
                    continue;
                }
                $driverOrders = $ordersByDriver[$externalRef] ?? [];
                if ($driverOrders === []) {
                    continue;
                }
                $processed = $this->syncOrdersForDriver($acc->getDriver(), $integration, $driverOrders);
                if ($processed > 0) {
                    $syncedAccounts++;
                    $totalOps += $processed;
                    $processedForIntegration += $processed;
                }
            }

            $report[] = [
                'integrationCode' => $integration->getCode(),
                'accounts' => count($accountsGroup),
                'orders' => count($orders),
                'processed' => $processedForIntegration,
            ];
        }

        return [
            'totalOps' => $totalOps,
            'syncedAccounts' => $syncedAccounts,
            'accountsTotal' => count($accounts),
            'integrationsTotal' => count($accountsByIntegration),
            'report' => $report,
        ];
    }

    private function syncOrdersForDriver(Driver $driver, DriverIntegration $integration, array $orders): int
    {
        if ($orders === []) {
            return 0;
        }

        $opRepo = $this->em->getRepository(PaymentOperation::class);
        $processed = 0;

        foreach ($orders as $ord) {
            $ref = (string) ($ord['order_reference'] ?? '');
            $price = is_array($ord['order_price'] ?? null) ? $ord['order_price'] : [];
            $net = (float) ($price['net_earnings'] ?? 0);
            $tip = (float) ($price['tip'] ?? 0);
            $bonus = (float) (($price['in_app_discount'] ?? 0) + ($price['cash_discount'] ?? 0));
            $methodRaw = strtolower((string) ($ord['payment_method'] ?? ''));
            $pmEnum = $methodRaw === 'cash' ? PaymentMethodType::CASH : PaymentMethodType::CB;
            $ts = (int) ($ord['payment_confirmed_timestamp'] ?? $ord['order_finished_timestamp'] ?? $ord['order_drop_off_timestamp'] ?? $ord['order_created_timestamp'] ?? time());
            $occurredAt = (new \DateTimeImmutable('@' . $ts))->setTimezone(new \DateTimeZone('UTC'));
            $status = strtoupper((string) ($ord['order_status'] ?? 'FINISHED'));
            $rideDistance = (float) ($ord['ride_distance'] / 1000 ?? 0);
            $existing = null;

            if ($ref !== '') {
                $existing = $opRepo->findOneBy([
                    'driver' => $driver,
                    'integrationCode' => $integration->getCode(),
                    'externalReference' => $ref,
                ]);
            }

            if ($existing instanceof PaymentOperation) {
                $existing->setOperationType('ORDER');
                $existing->setDirection('IN');
                $existing->setAmount(number_format($net, 3, '.', ''));
                $existing->setCurrency('EUR');
                $existing->setStatus($status);
                $existing->setDescription(null);
                $existing->setOriginalObject(is_array($ord) ? $ord : null);
                $existing->setPaymentMethodEnum($pmEnum);
                $existing->setTips(number_format($tip, 3, '.', ''));
                $existing->setBonus(number_format($bonus, 3, '.', ''));
                $existing->setOccurredAt($occurredAt);
                $existing->setRideDistance(number_format($rideDistance, 3, '.', ''));
                $processed++;
                continue;
            }

            $op = new PaymentOperation();
            $op->setDriver($driver);
            $op->setIntegrationCode($integration->getCode());
            $op->setOperationType('ORDER');
            $op->setDirection('IN');
            $op->setAmount(number_format($net, 3, '.', ''));
            $op->setCurrency('EUR');
            $op->setStatus($status);
            $op->setExternalReference($ref !== '' ? $ref : null);
            $op->setDescription(null);
            $op->setOriginalObject(is_array($ord) ? $ord : null);
            $op->setPaymentMethodEnum($pmEnum);
            $op->setTips(number_format($tip, 3, '.', ''));
            $op->setBonus(number_format($bonus, 3, '.', ''));
            $op->setOccurredAt($occurredAt);
            $op->setRideDistance(number_format($rideDistance, 3, '.', ''));
            $this->em->persist($op);
            $processed++;
        }

        $this->em->flush();
        return $processed;
    }

    private function groupOrdersByDriver(array $orders): array
    {
        $grouped = [];
        foreach ($orders as $ord) {
            $uuid = strtolower(trim((string) ($ord['driver_uuid'] ?? '')));
            if ($uuid === '') {
                continue;
            }
            if (!isset($grouped[$uuid])) {
                $grouped[$uuid] = [];
            }
            $grouped[$uuid][] = $ord;
        }
        return $grouped;
    }

    private function getAccessToken(DriverIntegration $integration): string
    {
        $key = $integration->getId() ?? $integration->getCode();
        if (array_key_exists($key, $this->tokenCache)) {
            return (string) $this->tokenCache[$key];
        }

        try {
            $cid = $integration->getBoltCustomerId();
            $secret = $integration->getBoltCustomerSecret();
            $scope = $integration->getBoltScope() ?? 'fleet-integration:api';
            $tokenPayload = ($cid && $secret) ? $this->bolt->getTokenUsing($cid, $secret, $scope) : $this->bolt->getToken();
            $accessToken = (string) ($tokenPayload['access_token'] ?? '');
            $this->tokenCache[$key] = $accessToken;
            if ($this->logger) {
                $this->logger->info('bolt_token_retrieved', [
                    'integration_code' => $integration->getCode(),
                    'has_access_token' => $accessToken !== '',
                ]);
            }
            return $accessToken;
        } catch (\Throwable $e) {
            if ($this->logger) {
                $this->logger->error('integration_sync_failed', [
                    'integration_code' => $integration->getCode(),
                    'error' => $e->getMessage(),
                ]);
            }
            $this->tokenCache[$key] = '';
            return '';
        }
    }

    private function extractOrders(array $ordersPayload): array
    {
        $orders = [];
        if (isset($ordersPayload['data'])) {
            if (isset($ordersPayload['data']['orders']) && is_array($ordersPayload['data']['orders'])) {
                $orders = $ordersPayload['data']['orders'];
            } elseif (is_array($ordersPayload['data'])) {
                foreach ($ordersPayload['data'] as $item) {
                    if (is_array($item) && isset($item['orders']) && is_array($item['orders'])) {
                        $orders = array_merge($orders, $item['orders']);
                    }
                }
            }
        }
        return $orders;
    }
}
