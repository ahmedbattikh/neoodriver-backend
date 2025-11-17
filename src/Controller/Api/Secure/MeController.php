<?php
declare(strict_types=1);

namespace App\Controller\Api\Secure;

use App\Entity\User;
use FOS\RestBundle\Context\Context;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class MeController extends AbstractFOSRestController
{
    #[Route('/api/secure/me', name: 'api_secure_me', methods: ['GET'])]
    public function __invoke(#[CurrentUser] ?User $user): Response
    {
        if (!$user) {
            $view = $this->view(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
            return $this->handleView($view);
        }

        $context = new Context();
        $context->addGroup('me:read');
        $view = $this->view($user, Response::HTTP_OK);
        $view->setContext($context);
        return $this->handleView($view);
    }
}