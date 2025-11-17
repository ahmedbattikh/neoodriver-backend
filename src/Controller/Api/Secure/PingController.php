<?php
declare(strict_types=1);

namespace App\Controller\Api\Secure;

use FOS\RestBundle\Context\Context;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class PingController extends AbstractFOSRestController
{
    #[Route('/api/secure/ping', name: 'api_secure_ping', methods: ['GET'])]
    public function ping(): Response
    {
        $data = [
            'status' => 'ok',
            'access' => 'secured',
            'time' => (new \DateTimeImmutable())->format(DATE_ATOM),
        ];
        $context = new Context();
        $context->addGroup('ping:read');
        $view = $this->view($data, Response::HTTP_OK);
        $view->setContext($context);
        return $this->handleView($view);
    }
}