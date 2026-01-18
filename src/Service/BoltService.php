<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Log\LoggerInterface;

final class BoltService
{
    public function __construct(
        private readonly string $oidcUrl,
        private readonly string $ordersUrl,
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $scope,
        private readonly ?LoggerInterface $logger = null,
    ) {}

    private function logInfo(string $event, array $context = []): void
    {
        if ($this->logger) {
            $this->logger->info($event, $context);
            return;
        }
        $this->fileLog('info', $event, $context);
    }
    private function logError(string $event, array $context = []): void
    {
        if ($this->logger) {
            $this->logger->error($event, $context);
            return;
        }
        $this->fileLog('error', $event, $context);
    }
    private function fileLog(string $level, string $event, array $context = []): void
    {
        $root = dirname(__DIR__, 2);
        $dir = $root . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'log';
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        $file = $dir . DIRECTORY_SEPARATOR . 'bolt.log';
        $line = sprintf(
            "%s %s %s %s%s",
            (new \DateTimeImmutable('now'))->format(\DateTimeInterface::ATOM),
            strtoupper($level),
            $event,
            json_encode($context, JSON_UNESCAPED_SLASHES),
            PHP_EOL
        );
        @file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }

    public function getToken(): array
    {
        $payload = http_build_query([
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'client_credentials',
            'scope' => $this->scope,
        ], '', '&');
        $resp = $this->curlPostForm($this->oidcUrl, $payload, [
            'Content-Type: application/x-www-form-urlencoded',
        ]);
        $data = $this->decodeJsonOrThrow($resp['body'], $resp['status']);
        $this->logInfo('bolt_token_response', [
            'using_custom_credentials' => false,
            'has_access_token' => array_key_exists('access_token', $data),
            'token_type' => $data['token_type'] ?? null,
            'expires_in' => $data['expires_in'] ?? null,
            'scope' => $data['scope'] ?? null,
        ]);
        return $data;
    }
    public function getTokenUsing(string $clientId, string $clientSecret, string $scope): array
    {
        $payload = http_build_query([
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'grant_type' => 'client_credentials',
            'scope' => $scope,
        ], '', '&');
        $resp = $this->curlPostForm($this->oidcUrl, $payload, [
            'Content-Type: application/x-www-form-urlencoded',
        ]);
        $data = $this->decodeJsonOrThrow($resp['body'], $resp['status']);
        $this->logInfo('bolt_token_response', [
            'using_custom_credentials' => true,
            'has_access_token' => array_key_exists('access_token', $data),
            'token_type' => $data['token_type'] ?? null,
            'expires_in' => $data['expires_in'] ?? null,
            'scope' => $data['scope'] ?? null,
        ]);
        return $data;
    }

    public function getFleetOrders(string $accessToken, int $offset, int $limit, array $companyIds, int $startTs, int $endTs, string $timeRangeFilterType = 'price_review'): array
    {
        $body = json_encode([
            'offset' => $offset,
            'limit' => $limit,
            'company_ids' => array_values(array_map(fn($v) => (int) $v, $companyIds)),
            'start_ts' => $startTs,
            'end_ts' => $endTs,
            'time_range_filter_type' => $timeRangeFilterType,
        ], JSON_UNESCAPED_SLASHES);
        $resp = $this->curlPostJson($this->ordersUrl, $body, [
            'Accept: application/json',
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ]);
        var_dump($this->ordersUrl);
        $data = $this->decodeJsonOrThrow($resp['body'], $resp['status']);
        $ordersCount = 0;
        if (isset($data['data'])) {
            if (isset($data['data']['orders']) && is_array($data['data']['orders'])) {
                $ordersCount = count($data['data']['orders']);
            } elseif (is_array($data['data'])) {
                foreach ($data['data'] as $item) {
                    if (is_array($item) && isset($item['orders']) && is_array($item['orders'])) {
                        $ordersCount += count($item['orders']);
                    }
                }
            }
        }

        var_dump([
            'offset' => $offset,
            'limit' => $limit,
            'start_ts' => $startTs,
            'end_ts' => $endTs,
            'company_ids' => array_values(array_map(fn($v) => (int) $v, $companyIds)),
            'orders_count' => $ordersCount,
        ]);
        $this->logInfo('bolt_fleet_orders_response', [
            'offset' => $offset,
            'limit' => $limit,
            'start_ts' => $startTs,
            'end_ts' => $endTs,
            'company_ids' => array_values(array_map(fn($v) => (int) $v, $companyIds)),
            'orders_count' => $ordersCount,
        ]);
        return $data;
    }

    private function curlPostForm(string $url, string $body, array $headers): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 20,
        ]);
        $result = curl_exec($ch);
        if ($result === false) {
            $err = curl_error($ch);
            curl_close($ch);
            $this->logError('bolt_post_form_failed', ['error' => $err]);
            throw new \RuntimeException('Bolt request failed: ' . $err);
        }
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE) ?: 0;
        curl_close($ch);
        $this->logInfo('bolt_http_form', [
            'url' => $url,
            'status' => $status,
            'response_length' => strlen((string) $result),
        ]);
        return ['status' => $status, 'body' => (string) $result];
    }

    private function curlPostJson(string $url, string $body, array $headers): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
        ]);
        $result = curl_exec($ch);
        if ($result === false) {
            $err = curl_error($ch);
            curl_close($ch);
            $this->logError('bolt_post_json_failed', ['error' => $err]);
            throw new \RuntimeException('Bolt request failed: ' . $err);
        }
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE) ?: 0;
        curl_close($ch);
        $this->logInfo('bolt_http_json', [
            'url' => $url,
            'status' => $status,
            'request_length' => strlen($body),
            'response_length' => strlen((string) $result),
        ]);
        return ['status' => $status, 'body' => (string) $result];
    }

    private function decodeJsonOrThrow(string $body, int $status): array
    {
        $payload = json_decode($body, true);
        if (!is_array($payload)) {
            $this->logError('bolt_invalid_json', ['status' => $status]);
            throw new \RuntimeException('Bolt invalid response: ' . $body);
        }
        if ($status < 200 || $status >= 300) {
            $this->logError('bolt_non_2xx', ['status' => $status]);
            $msg = (string) ($payload['error_description'] ?? $payload['message'] ?? 'error');
            throw new \RuntimeException('Bolt HTTP ' . $status . ': ' . $msg);
        }
        return $payload;
    }
}
