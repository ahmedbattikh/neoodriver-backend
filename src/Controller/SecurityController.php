<?php
declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

final class SecurityController extends AbstractController
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

    /**
     * Admin login form (GET). The POST is handled by the form_login authenticator.
     */
    #[Route('/admin/login', name: 'admin_login', methods: ['GET', 'POST'])]
    public function adminLogin(AuthenticationUtils $authUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('admin');
        }

        return $this->render('security/login.html.twig', [
            'last_username' => $authUtils->getLastUsername(),
            'error' => $authUtils->getLastAuthenticationError(),
        ]);
    }
}