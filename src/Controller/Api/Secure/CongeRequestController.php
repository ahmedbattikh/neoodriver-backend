<?php

declare(strict_types=1);

namespace App\Controller\Api\Secure;

use App\Entity\CongeRequest;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/me/conge-requests')]
final class CongeRequestController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    #[Route('', name: 'api_me_conge_requests_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $driver = $user->getDriverProfile();
        $items = $this->em->getRepository(CongeRequest::class)->findBy(['driver' => $driver], ['createdAt' => 'DESC']);
        $rows = [];
        foreach ($items as $r) {
            if ($r instanceof CongeRequest) {
                $rows[] = [
                    'id' => $r->getId(),
                    'amount' => $r->getAmount(),
                    'status' => $r->getStatus()->value,
                    'description' => $r->getDescription(),
                    'createdAt' => $r->getCreatedAt()->format('c'),
                    'approvedAt' => $r->getApprovedAt()?->format('c'),
                ];
            }
        }
        return $this->json(['items' => $rows], Response::HTTP_OK);
    }

    #[Route('', name: 'api_me_conge_requests_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $driver = $user->getDriverProfile();
        $amount = (string) $request->request->get('amount', '0');
        $description = (string) $request->request->get('description', '');
        $req = new CongeRequest();
        $req->setDriver($driver);
        $req->setAmount(number_format((float) $amount, 2, '.', ''));
        $req->setDescription($description !== '' ? $description : null);
        $this->em->persist($req);
        $this->em->flush();
        return $this->json([
            'id' => $req->getId(),
            'amount' => $req->getAmount(),
            'status' => $req->getStatus()->value,
        ], Response::HTTP_CREATED);
    }
}
