<?php

declare(strict_types=1);

namespace App\Controller\Api\Secure;

use App\Entity\AdvanceRequest;
use App\Entity\Balance;
use App\Entity\NeooFee;
use App\Entity\PaymentOperation;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/me/advance-requests')]
final class AdvanceRequestController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    #[Route('', name: 'api_me_advance_requests_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $driver = $user->getDriverProfile();
        $items = $this->em->getRepository(AdvanceRequest::class)->findBy(['driver' => $driver], ['createdAt' => 'DESC']);
        $rows = [];
        foreach ($items as $r) {
            if ($r instanceof AdvanceRequest) {
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

    #[Route('/allowed-amount', name: 'api_me_advance_requests_allowed_amount', methods: ['GET'])]
    public function allowedAmount(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $driver = $user->getDriverProfile();
        $weekStart = new \DateTimeImmutable('monday this week 00:00:00');
        $weekEnd = new \DateTimeImmutable('monday next week 00:00:00');
        $row = (array) ($this->em->createQueryBuilder()
            ->select("SUM(CASE WHEN LOWER(o.direction) IN ('in','credit') THEN o.amount ELSE 0 END) AS totalIn")
            ->from(PaymentOperation::class, 'o')
            ->where('o.driver = :driver')
            ->andWhere('o.occurredAt >= :start')
            ->andWhere('o.occurredAt < :end')
            ->setParameter('driver', $driver)
            ->setParameter('start', $weekStart)
            ->setParameter('end', $weekEnd)
            ->getQuery()
            ->getOneOrNullResult() ?? []);
        $weekIn = (float) (($row['totalIn'] ?? 0) ?: 0);
        $fee = $this->em->createQueryBuilder()
            ->select('f')
            ->from(NeooFee::class, 'f')
            ->where(':ca >= f.start')
            ->andWhere(':ca <= f.end')
            ->setParameter('ca', $weekIn)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        $taux = $fee instanceof NeooFee ? (float) $fee->getTaux() : 0.0;
        $balance = $this->em->getRepository(Balance::class)->findOneBy(['driver' => $driver]);
        $sold = $balance ? (float) $balance->getSold() : 0.0;
        $neoofee = $weekIn * $taux;
        $allowed = max(0.0, $sold - $neoofee);
        return $this->json([
            'allowedAmount' => number_format($allowed, 3, '.', ''),
            'sold' => number_format($sold, 3, '.', ''),
            'neoofee' => number_format($neoofee, 3, '.', ''),
            'taux' => number_format($taux, 3, '.', ''),
            'weekTotalIn' => number_format($weekIn, 3, '.', ''),
            'weekStart' => $weekStart->format('Y-m-d'),
            'weekEnd' => $weekEnd->format('Y-m-d'),
        ], Response::HTTP_OK);
    }

    #[Route('', name: 'api_me_advance_requests_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $driver = $user->getDriverProfile();
        $amount = (string) $request->request->get('amount', '0');
        $description = (string) $request->request->get('description', '');
        $weekStart = new \DateTimeImmutable('monday this week 00:00:00');
        $weekEnd = new \DateTimeImmutable('monday next week 00:00:00');
        $row = (array) ($this->em->createQueryBuilder()
            ->select("SUM(CASE WHEN LOWER(o.direction) IN ('in','credit') THEN o.amount ELSE 0 END) AS totalIn")
            ->from(PaymentOperation::class, 'o')
            ->where('o.driver = :driver')
            ->andWhere('o.occurredAt >= :start')
            ->andWhere('o.occurredAt < :end')
            ->setParameter('driver', $driver)
            ->setParameter('start', $weekStart)
            ->setParameter('end', $weekEnd)
            ->getQuery()
            ->getOneOrNullResult() ?? []);
        $weekIn = (float) (($row['totalIn'] ?? 0) ?: 0);
        $fee = $this->em->createQueryBuilder()
            ->select('f')
            ->from(NeooFee::class, 'f')
            ->where(':ca >= f.start')
            ->andWhere(':ca <= f.end')
            ->setParameter('ca', $weekIn)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        $taux = $fee instanceof NeooFee ? (float) $fee->getTaux() : 0.0;
        $balance = $this->em->getRepository(Balance::class)->findOneBy(['driver' => $driver]);
        $sold = $balance ? (float) $balance->getSold() : 0.0;
        $neoofee = $weekIn * $taux;
        $allowed = max(0.0, $sold - $neoofee);
        $requested = (float) $amount;
        if ($requested > $allowed) {
            return $this->json([
                'error' => 'amount_exceeds_allowed',
                'message' => 'Requested amount exceeds allowed advance',
                'allowedAmount' => number_format($allowed, 3, '.', ''),
                'requestedAmount' => number_format($requested, 3, '.', ''),
                'sold' => number_format($sold, 3, '.', ''),
                'neoofee' => number_format($neoofee, 3, '.', ''),
                'taux' => number_format($taux, 3, '.', ''),
                'weekTotalIn' => number_format($weekIn, 3, '.', ''),
            ], Response::HTTP_BAD_REQUEST);
        }
        $req = new AdvanceRequest();
        $req->setDriver($driver);
        $req->setAmount(number_format((float)$amount, 3, '.', ''));
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
