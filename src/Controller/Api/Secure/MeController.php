<?php
declare(strict_types=1);

namespace App\Controller\Api\Secure;

use App\Entity\User;
use App\Entity\Goals;
use FOS\RestBundle\Context\Context;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class MeController extends AbstractFOSRestController
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    #[Route('/api/secure/me', name: 'api_secure_me', methods: ['GET'])]
    public function __invoke(#[CurrentUser] ?User $user): Response
    {
        if (!$user) {
            $view = $this->view(['message' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
            return $this->handleView($view);
        }

        $goalsArr = [];
        $driver = $user->getDriverProfile();
        if ($driver) {
            $class = $driver->getClassDriver();
            $all = $this->em->getRepository(Goals::class)->findBy(['enabled' => true], ['updatedAt' => 'DESC']);
            foreach ($all as $g) {
                $targets = $g->getTargetClasses();
                if (in_array($class, $targets, true)) {
                    $goalsArr[] = [
                        'id' => $g->getId(),
                        'name' => $g->getName(),
                        'amount' => $g->getAmount(),
                        'frequency' => $g->getFrequency(),
                        'targetClasses' => $targets,
                        'enabled' => $g->isEnabled(),
                        'updatedAt' => $g->getUpdatedAt()->format(DATE_ATOM),
                        'createdAt' => $g->getCreatedAt()->format(DATE_ATOM),
                    ];
                }
            }
        }

        $context = new Context();
        $context->addGroup('me:read');
        $view = $this->view([
            'user' => $user,
            'goals' => $goalsArr,
        ], Response::HTTP_OK);
        $view->setContext($context);
        return $this->handleView($view);
    }
}
