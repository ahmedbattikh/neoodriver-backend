<?php
declare(strict_types=1);

namespace App\Command;

use App\Entity\DriverIntegration;
use App\Entity\DriverIntegrationAccount;
use App\Entity\PaymentOperation;
use App\Enum\PaymentMethodType;
use App\Service\BoltService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:integrations:sync', description: 'Sync integration orders for all drivers')]
final class SyncDriverIntegrationsCommand extends Command
{
    private array $tokenCache = [];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly BoltService $bolt,
        private readonly ?LoggerInterface $logger = null,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('start', null, InputOption::VALUE_REQUIRED, 'Start date/time in UTC (e.g. 2026-03-01 00:00:00)')
            ->addOption('end', null, InputOption::VALUE_REQUIRED, 'End date/time in UTC (e.g. 2026-03-02 00:00:00)')
            ->addOption('hours', null, InputOption::VALUE_REQUIRED, 'Hours back from now in UTC', '2');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $tz = new \DateTimeZone('UTC');
        $startRaw = $input->getOption('start');
        $endRaw = $input->getOption('end');
        $hoursRaw = $input->getOption('hours');

        try {
            if ($startRaw || $endRaw) {
                $start = $startRaw ? new \DateTimeImmutable((string) $startRaw, $tz) : new \DateTimeImmutable('today 00:00:00', $tz);
                $end = $endRaw ? new \DateTimeImmutable((string) $endRaw, $tz) : new \DateTimeImmutable('tomorrow 00:00:00', $tz);
            } else {
                $hours = is_numeric($hoursRaw) ? (int) $hoursRaw : 0;
                if ($hours <= 0) {
                    $io->error('Hours must be a positive number.');
                    return Command::FAILURE;
                }
                $end = new \DateTimeImmutable('now', $tz);
                $start = $end->modify(sprintf('-%d hours', $hours));
            }
        } catch (\Throwable $e) {
            $io->error('Invalid start/end date format.');
            return Command::FAILURE;
        }
        if ($end <= $start) {
            $io->error('End date must be after start date.');
            return Command::FAILURE;
        }
        $startTs = $start->getTimestamp();
        $endTs = $end->getTimestamp();

        $accounts = $this->em->getRepository(DriverIntegrationAccount::class)->findAll();
        if (!$accounts) {
            $io->success('No integration accounts found.');
            return Command::SUCCESS;
        }

        $totalOps = 0;
        $syncedAccounts = 0;

        foreach ($accounts as $acc) {
            if (!$acc instanceof DriverIntegrationAccount) {
                continue;
            }
            $integration = $acc->getIntegration();
            if (!$integration instanceof DriverIntegration || !$integration->isEnabled()) {
                continue;
            }
            $processed = $this->syncAccount($acc, $integration, $startTs, $endTs);
            if ($processed > 0) {
                $syncedAccounts++;
                $totalOps += $processed;
            }
        }

        $io->success(sprintf('Synced %d operations across %d accounts.', $totalOps, $syncedAccounts));
        return Command::SUCCESS;
    }

    private function syncAccount(DriverIntegrationAccount $acc, DriverIntegration $integration, int $startTs, int $endTs): int
    {
        $accessToken = $this->getAccessToken($integration);
        if ($accessToken === '') {
            return 0;
        }

        $companyIds = $integration->getBoltCompanyIds();
        $ordersPayload = $this->bolt->getFleetOrders($accessToken, 0, 1000, $companyIds, $startTs, $endTs, 'price_review');
        $orders = $this->extractOrders($ordersPayload);

        $targetDriverUuid = (string) $acc->getIdDriver();
        if ($targetDriverUuid === '') {
            return 0;
        }

        $opRepo = $this->em->getRepository(PaymentOperation::class);
        $driver = $acc->getDriver();
        $processed = 0;

        foreach ($orders as $ord) {
            $uuid = (string) ($ord['driver_uuid'] ?? '');
            if ($uuid === '' || strcasecmp($uuid, $targetDriverUuid) !== 0) {
                continue;
            }
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
