<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Balance;
use App\Entity\CongeRequest;
use App\Enum\CongeRequestStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_SUPER_ADMIN')]
final class CongeRequestAdminController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    #[Route('/admin/conge-requests/{id}/approve', name: 'admin_conge_request_approve', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function approve(Request $request, int $id): Response
    {
        $req = $this->em->getRepository(CongeRequest::class)->find($id);
        if (!$req instanceof CongeRequest) {
            return $this->redirectToRoute('admin');
        }
        $driver = $req->getDriver();
        $user = $driver->getUser();
        $token = (string) $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid('approve_conge_' . $req->getId(), $token)) {
            return $this->redirectToRoute('admin_user_show', ['id' => $user?->getId()]);
        }
        $req->setStatus(CongeRequestStatus::APPROVED);
        $req->setApprovedAt(new \DateTimeImmutable('now'));

        $balance = $this->em->getRepository(Balance::class)->findOneBy(['driver' => $driver]);
        if (!$balance instanceof Balance) {
            $balance = new Balance();
            $balance->setDriver($driver);
            $this->em->persist($balance);
        }
        $current = (float) $balance->getSoldConge();
        $delta = (float) $req->getAmount();
        $balance->setSoldConge(number_format($current - $delta, 2, '.', ''));
        $balance->setLastUpdate(new \DateTimeImmutable('now'));
        $this->em->flush();
        return $this->redirectToRoute('admin_user_show', ['id' => $user?->getId(), 'tab' => 'conge']);
    }

    #[Route('/admin/conge-requests/{id}/reject', name: 'admin_conge_request_reject', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function reject(Request $request, int $id): Response
    {
        $req = $this->em->getRepository(CongeRequest::class)->find($id);
        if (!$req instanceof CongeRequest) {
            return $this->redirectToRoute('admin');
        }
        $driver = $req->getDriver();
        $user = $driver->getUser();
        $token = (string) $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid('reject_conge_' . $req->getId(), $token)) {
            return $this->redirectToRoute('admin_user_show', ['id' => $user?->getId()]);
        }
        $note = (string) $request->request->get('admin_note', '');
        $req->setAdminNote($note !== '' ? $note : null);
        $req->setStatus(CongeRequestStatus::REJECTED);
        $req->setApprovedAt(null);
        $this->em->flush();
        return $this->redirectToRoute('admin_user_show', ['id' => $user?->getId(), 'tab' => 'conge']);
    }
}
