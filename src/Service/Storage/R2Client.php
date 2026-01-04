<?php

declare(strict_types=1);

namespace App\Service\Storage;

use Aws\Credentials\Credentials;
use Aws\S3\S3Client;
use Psr\Log\LoggerInterface;

final class R2Client
{
    private S3Client $s3;
    public function __construct(
        private readonly string $accountId,
        private readonly string $accessKeyId,
        private readonly string $secretAccessKey,
        private readonly string $bucket,
        private readonly string $endpoint,
        private readonly ?LoggerInterface $logger = null,
    ) {
        $credentials = new Credentials($this->accessKeyId, $this->secretAccessKey);
        $this->s3 = new S3Client([
            'version' => 'latest',
            'region' => 'auto',
            'endpoint' => rtrim($this->endpoint, '/'),
            'credentials' => $credentials,
            'use_path_style_endpoint' => false,
        ]);
    }

    public function putObject(string $key, string $body = '', string $contentType = 'application/octet-stream', array $extraHeaders = [], bool $unsignedPayload = true): void
    {
        try {
            $this->s3->putObject([
                'Bucket' => $this->bucket,
                'Key' => $key,
                'Body' => $body,
                'ContentType' => $contentType,
            ]);
        } catch (\Throwable $e) {
            if ($this->logger) {
                $this->logger->error('R2 PUT failed', ['key' => $key, 'error' => $e->getMessage()]);
            }
            throw new \RuntimeException('R2 upload failed: ' . $e->getMessage());
        }
    }

    public function ensureUserFolders(string $reference): void
    {
        foreach (['company', 'driver', 'vehicle', 'user', 'expense'] as $folder) {
            $this->putObject($reference . '/' . $folder . '/.keep');
        }
    }

    public function getSignedUrl(string $key, int $expires = 900): string
    {
        $cmd = $this->s3->getCommand('GetObject', [
            'Bucket' => $this->bucket,
            'Key' => $key,
        ]);
        $req = $this->s3->createPresignedRequest($cmd, '+' . max(1, $expires) . ' seconds');
        return (string) $req->getUri();
    }
}
