<?php

declare(strict_types=1);

namespace App\Service\Storage;

use Psr\Log\LoggerInterface;

final class R2Client
{
    public function __construct(
        private readonly string $accountId,
        private readonly string $accessKeyId,
        private readonly string $secretAccessKey,
        private readonly string $bucket,
        private readonly string $endpoint,
        private readonly ?LoggerInterface $logger = null,
    ) {}

    public function putObject(string $key, string $body = '', string $contentType = 'application/octet-stream', array $extraHeaders = [], bool $unsignedPayload = true): void
    {
        $url = rtrim($this->endpoint, '/') . '/' . rawurlencode($this->bucket) . '/' . str_replace('%2F', '/', rawurlencode($key));
        $payloadHash = $unsignedPayload ? 'UNSIGNED-PAYLOAD' : hash('sha256', $body);
        $dateTime = gmdate('Ymd\\THis\\Z');
        $date = gmdate('Ymd');
        $host = parse_url($this->endpoint, PHP_URL_HOST) ?: $this->endpoint;

        $headers = [
            'host' => $host,
            'x-amz-content-sha256' => $payloadHash,
            'x-amz-date' => $dateTime,
            'content-type' => $contentType,
        ];
        foreach ($extraHeaders as $k => $v) {
            $headers[strtolower($k)] = $v;
        }

        $signedHeaderKeys = array_keys($headers);
        sort($signedHeaderKeys);
        $canonicalHeaders = '';
        foreach ($signedHeaderKeys as $k) {
            $canonicalHeaders .= $k . ':' . trim($headers[$k]) . "\n";
        }
        $signedHeaders = implode(';', $signedHeaderKeys);

        $uri = '/' . rawurlencode($this->bucket) . '/' . str_replace('%2F', '/', rawurlencode($key));
        $canonicalRequest = "PUT\n{$uri}\n\n{$canonicalHeaders}\n{$signedHeaders}\n{$payloadHash}";
        $scope = "{$date}/auto/s3/aws4_request";
        $stringToSign = "AWS4-HMAC-SHA256\n{$dateTime}\n{$scope}\n" . hash('sha256', $canonicalRequest);

        $kDate = hash_hmac('sha256', $date, 'AWS4' . $this->secretAccessKey, true);
        $kRegion = hash_hmac('sha256', 'auto', $kDate, true);
        $kService = hash_hmac('sha256', 's3', $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
        $signature = hash_hmac('sha256', $stringToSign, $kSigning);

        $auth = sprintf(
            'AWS4-HMAC-SHA256 Credential=%s/%s, SignedHeaders=%s, Signature=%s',
            $this->accessKeyId,
            $scope,
            $signedHeaders,
            $signature
        );

        $contentLength = strlen($body);
        $headerLines = [
            'Authorization: ' . $auth,
            'x-amz-content-sha256: ' . $payloadHash,
            'x-amz-date: ' . $dateTime,
            'Content-Type: ' . $contentType,
            'Host: ' . $host,
            'Content-Length: ' . $contentLength,
        ];
        foreach ($extraHeaders as $k => $v) {
            $headerLines[] = $k . ': ' . $v;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'PUT',
                'header' => implode("\r\n", $headerLines),
                'content' => $body,
                'protocol_version' => 1.1,
                'ignore_errors' => true,
                'timeout' => 20,
            ],
        ]);

        $res = @file_get_contents($url, false, $context);
        $statusLine = is_array($http_response_header ?? null) ? ($http_response_header[0] ?? '') : '';
        if (!str_contains($statusLine, ' 200 ') && !str_contains($statusLine, ' 201 ') && !str_contains($statusLine, ' 204 ')) {
            if ($this->logger) {
                $this->logger->error('R2 PUT failed', ['status' => $statusLine, 'key' => $key, 'body' => $res]);
            }
            throw new \RuntimeException('R2 upload failed: ' . $statusLine . ' ' . $auth);
        }
    }

    public function ensureUserFolders(string $reference): void
    {
        foreach (['company', 'driver', 'vehicle'] as $folder) {
            $this->putObject($reference . '/' . $folder . '/.keep');
        }
    }
}
