<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

final class HealthController
{
    #[Route('/health', name: 'app_health', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        $tag = $_SERVER['DEPLOY_TAG'] ?? $_ENV['DEPLOY_TAG'] ?? null;

        return new JsonResponse([
            'status' => 'ok',
            'tag' => $tag,
            'time' => (new \DateTimeImmutable())->format(DATE_ATOM),
        ]);
    }
}