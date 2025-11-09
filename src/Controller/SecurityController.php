<?php
declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class SecurityController
{
    /**
     * This endpoint is intercepted by the Security firewall's json_login authenticator.
     * Defining a controller prevents a 404 due to a missing controller on the route.
     */
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(): Response
    {
        return new Response('', Response::HTTP_NOT_FOUND);
    }

    /**
     * This endpoint is intercepted by the Gesdinet refresh token authenticator.
     * Defining a controller prevents a 404 due to a missing controller on the route.
     */
    public function refresh(): Response
    {
        return new Response('', Response::HTTP_NOT_FOUND);
    }
}