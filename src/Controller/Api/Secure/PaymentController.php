<?php

declare(strict_types=1);

namespace App\Controller\Api\Secure;

use App\Entity\Driver;
use App\Entity\ExpenseNote;
use App\Entity\NeooConfig;
use App\Entity\NeooFee;
use App\Entity\PaymentOperation;
use App\Entity\User;
use App\Enum\PaymentMethodType;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class PaymentController extends AbstractFOSRestController
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    #[Route('/api/secure/payslip', name: 'api_secure_payslip', methods: ['GET'])]
    public function payslip(Request $request, #[CurrentUser] ?User $user): Response
    {
        if (!$user instanceof User) {
            $view = $this->view(['error' => 'unauthorized'], Response::HTTP_UNAUTHORIZED);
            return $this->handleView($view);
        }
        $driver = $user->getDriverProfile();
        if (!$driver instanceof Driver) {
            $view = $this->view(['error' => 'driver_not_found'], Response::HTTP_NOT_FOUND);
            return $this->handleView($view);
        }
        $dateBeginParam = (string) $request->query->get('dateBegin', '');
        $dateEndParam = (string) $request->query->get('dateEnd', '');
        if ($dateBeginParam === '' || $dateEndParam === '') {
            $view = $this->view(['error' => 'dateBegin and dateEnd are required'], Response::HTTP_BAD_REQUEST);
            return $this->handleView($view);
        }
        $dateBegin = \DateTimeImmutable::createFromFormat('Y-m-d', $dateBeginParam);
        $dateEnd = \DateTimeImmutable::createFromFormat('Y-m-d', $dateEndParam);
        if (!$dateBegin instanceof \DateTimeImmutable || $dateBegin->format('Y-m-d') !== $dateBeginParam) {
            $view = $this->view(['error' => 'dateBegin must be in Y-m-d format'], Response::HTTP_BAD_REQUEST);
            return $this->handleView($view);
        }
        if (!$dateEnd instanceof \DateTimeImmutable || $dateEnd->format('Y-m-d') !== $dateEndParam) {
            $view = $this->view(['error' => 'dateEnd must be in Y-m-d format'], Response::HTTP_BAD_REQUEST);
            return $this->handleView($view);
        }
        $dateBegin = new \DateTimeImmutable($dateBegin->format('Y-m-d 00:00:00'));
        $dateEnd = new \DateTimeImmutable($dateEnd->format('Y-m-d 00:00:00'));
        if ($dateEnd <= $dateBegin) {
            $view = $this->view(['error' => 'dateEnd must be after dateBegin'], Response::HTTP_BAD_REQUEST);
            return $this->handleView($view);
        }
        $maxEnd = $dateBegin->modify('+1 month');
        if ($dateEnd > $maxEnd) {
            $view = $this->view(['error' => 'date range must not exceed one month'], Response::HTTP_BAD_REQUEST);
            return $this->handleView($view);
        }
        $data = $this->buildNeooData($driver, $dateBegin, $dateEnd);
        $data['neooStart'] = $dateBegin->format('Y-m-d');
        $data['neooEnd'] = $dateEnd->format('Y-m-d');
        $view = $this->view($data, Response::HTTP_OK);
        return $this->handleView($view);
    }

    private function buildNeooData(Driver $driver, \DateTimeImmutable $neooStart, \DateTimeImmutable $neooEnd): array
    {
        $neooEndQuery = $neooEnd->modify('+1 day');
        $neooTotalIn = 0.0;
        $neooFixed = 0.0;
        $neooExpenseGroups = [];
        $neooExpenseTotal = 0.0;
        $neooVatRate = 0.20;
        $neooVatRecoverable = 0.0;
        $neooC27 = 0.0;
        $neooKm = 0.0;
        $neooFraisKm = 0.0;
        $neooIk = 0.0;
        $neooCpTaux = 0.0;
        $neooCpIndemnite = null;
        $neooUrssaf = 0.0;
        $neooNetSocial = 0.0;
        $neooNetSocialNumerator = 0.0;
        $neooSalaireBrutCotisations = 0.0;
        $neooTauxPas = 0.0;
        $neooMontantPas = 0.0;
        $neooAcompte = 0.0;
        $neooNetSocialAfter = 0.0;
        $neooRemboursementsNonImposables = 0.0;
        $neooNetFinalPayer = 0.0;
        $neooCommissionVariable = 0.0;
        $neooTotalCommission = 0.0;
        $neooReversementBrut = 0.0;
        $row = $this->em->createQueryBuilder()
            ->select("SUM(CASE WHEN LOWER(o.direction) IN ('in','credit') THEN o.amount ELSE 0 END) AS totalIn")
            ->addSelect("SUM(COALESCE(o.rideDistance, 0)) AS totalKm")
            ->from(PaymentOperation::class, 'o')
            ->where('o.driver = :driver')
            ->andWhere('o.occurredAt >= :start')
            ->andWhere('o.occurredAt < :end')
            ->setParameter('driver', $driver)
            ->setParameter('start', $neooStart)
            ->setParameter('end', $neooEndQuery)
            ->getQuery()
            ->getOneOrNullResult();
        $neooTotalIn = (float) (($row['totalIn'] ?? 0) ?: 0);
        $neooKm = (float) (($row['totalKm'] ?? 0) ?: 0);
        $cfg = $this->em->getRepository(NeooConfig::class)->findOneBy([], ['updatedAt' => 'DESC']);
        if ($cfg instanceof NeooConfig) {
            $neooFraisKm = (float) $cfg->getFraisKm();
            $neooCpTaux = (float) $cfg->getTauxConge();
            $neooUrssaf = (float) $cfg->getTauxUrssaf();
            $neooTauxPas = (float) $cfg->getTauxPas();
        }
        $periodStart = $neooStart;
        $periodEnd = $neooEndQuery;
        $weekStart = (new \DateTimeImmutable($periodStart->format('Y-m-d') . ' 00:00:00'))->modify('monday this week 00:00:00');
        while ($weekStart < $periodEnd) {
            $weekEnd = $weekStart->modify('+7 days');
            $windowStart = $periodStart > $weekStart ? $periodStart : $weekStart;
            $windowEnd = $periodEnd < $weekEnd ? $periodEnd : $weekEnd;
            if ($windowEnd <= $windowStart) {
                $weekStart = $weekEnd;
                continue;
            }
            $windowDays = (int) $windowStart->diff($windowEnd)->days;
            $weekFraction = $windowDays / 7;
            $weekRow = $this->em->createQueryBuilder()
                ->select("SUM(CASE WHEN LOWER(o.direction) IN ('in','credit') THEN o.amount ELSE 0 END) AS totalIn")
                ->from(PaymentOperation::class, 'o')
                ->where('o.driver = :driver')
                ->andWhere('o.occurredAt >= :start')
                ->andWhere('o.occurredAt < :end')
                ->setParameter('driver', $driver)
                ->setParameter('start', $windowStart)
                ->setParameter('end', $windowEnd)
                ->getQuery()
                ->getOneOrNullResult();
            $weekTotalIn = (float) (($weekRow['totalIn'] ?? 0) ?: 0);
            $weekFee = $this->em->createQueryBuilder()
                ->select('f')
                ->from(NeooFee::class, 'f')
                ->where(':ca >= f.start')
                ->andWhere(':ca <= f.end')
                ->setParameter('ca', $weekTotalIn)
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
            $weeklyFixed = $weekFee instanceof NeooFee ? (float) $weekFee->getTaux() : 0.0;
            $neooFixed += $weeklyFixed * $weekFraction;
            $weekStart = $weekEnd;
        }
        $groups = $this->em->createQueryBuilder()
            ->select('n.type AS type, SUM(n.amountTtc) AS total')
            ->from(ExpenseNote::class, 'n')
            ->where('n.driver = :driver')
            ->andWhere('n.noteDate >= :start')
            ->andWhere('n.noteDate <= :end')
            ->groupBy('n.type')
            ->setParameter('driver', $driver)
            ->setParameter('start', $neooStart, 'date_immutable')
            ->setParameter('end', $neooEndQuery, 'date_immutable')
            ->getQuery()
            ->getArrayResult();
        $items = [];
        foreach ($groups as $g) {
            $label = is_object($g['type']) ? (string) $g['type']->value : (string) $g['type'];
            $val = (float) (($g['total'] ?? 0) ?: 0);
            $neooExpenseGroups[$label] = $val;
            $neooExpenseTotal += $val;
        }
        if (count($neooExpenseGroups) === 0) {
            $items = $this->em->createQueryBuilder()
                ->select('n')
                ->from(ExpenseNote::class, 'n')
                ->where('n.driver = :driver')
                ->andWhere('n.noteDate >= :startDate')
                ->andWhere('n.noteDate <= :endDate')
                ->setParameter('driver', $driver)
                ->setParameter('startDate', $neooStart, 'date_immutable')
                ->setParameter('endDate', $neooEndQuery, 'date_immutable')
                ->getQuery()
                ->getResult();
            foreach ($items as $n) {
                if ($n instanceof ExpenseNote) {
                    $label = $n->getType();
                    $val = (float) $n->getAmountTtc();
                    $neooExpenseGroups[$label] = ($neooExpenseGroups[$label] ?? 0.0) + $val;
                    $neooExpenseTotal += $val;
                }
            }
        }
        if (count($neooExpenseGroups) === 0) {
            $groups = $this->em->createQueryBuilder()
                ->select('n.type AS type, SUM(n.amountTtc) AS total')
                ->from(ExpenseNote::class, 'n')
                ->where('n.driver = :driver')
                ->andWhere('n.createdAt >= :start')
                ->andWhere('n.createdAt < :end')
                ->groupBy('n.type')
                ->setParameter('driver', $driver)
                ->setParameter('start', $neooStart, 'datetime_immutable')
                ->setParameter('end', $neooEndQuery, 'datetime_immutable')
                ->getQuery()
                ->getArrayResult();
            foreach ($groups as $g) {
                $label = is_object($g['type']) ? (string) $g['type']->value : (string) $g['type'];
                $val = (float) (($g['total'] ?? 0) ?: 0);
                $neooExpenseGroups[$label] = $val;
                $neooExpenseTotal += $val;
            }
        }
        if (count($neooExpenseGroups) === 0) {
            $items = $this->em->createQueryBuilder()
                ->select('n')
                ->from(ExpenseNote::class, 'n')
                ->where('n.driver = :driver')
                ->andWhere('n.createdAt >= :startDate')
                ->andWhere('n.createdAt < :endDate')
                ->setParameter('driver', $driver)
                ->setParameter('startDate', $neooStart, 'datetime_immutable')
                ->setParameter('endDate', $neooEndQuery, 'datetime_immutable')
                ->getQuery()
                ->getResult();
            foreach ($items as $n) {
                if ($n instanceof ExpenseNote) {
                    $label = $n->getType();
                    $val = (float) $n->getAmountTtc();
                    $neooExpenseGroups[$label] = ($neooExpenseGroups[$label] ?? 0.0) + $val;
                    $neooExpenseTotal += $val;
                }
            }
        }
        $neooVatRecoverable = $neooVatRate > 0 ? ($neooExpenseTotal * $neooVatRate / (1 + $neooVatRate)) : 0.0;
        $neooC27 = $neooExpenseTotal - $neooVatRecoverable;
        $neooIk = $neooKm * $neooFraisKm;
        $netSocialDenominator = (1 / 0.78) + ($neooUrssaf / 100);
        $netSocialNumerator = $neooTotalIn - $neooFixed - $neooC27 - $neooIk;
        $neooNetSocialNumerator = $netSocialNumerator;
        $neooNetSocial = $netSocialDenominator > 0 ? ($netSocialNumerator / $netSocialDenominator) : 0.0;
        $neooSalaireBrutCotisations = $neooNetSocial / 0.78;
        $neooMontantPas = $neooNetSocial * ($neooTauxPas / 100);
        $advanceRow = $this->em->createQueryBuilder()
            ->select('SUM(o.amount) AS totalAdvance')
            ->from(PaymentOperation::class, 'o')
            ->where('o.driver = :driver')
            ->andWhere('(o.operationType = :opType OR o.paymentMethod = :cash)')
            ->andWhere('o.occurredAt >= :start')
            ->andWhere('o.occurredAt < :end')
            ->setParameter('driver', $driver)
            ->setParameter('opType', 'CASH_ADVANCE')
            ->setParameter('cash', PaymentMethodType::CASH)
            ->setParameter('start', $neooStart)
            ->setParameter('end', $neooEndQuery)
            ->getQuery()
            ->getOneOrNullResult();
        $neooAcompte = (float) (($advanceRow['totalAdvance'] ?? 0) ?: 0);
        $neooCpIndemnite = $neooSalaireBrutCotisations * ($neooCpTaux / 100);
        $neooNetSocialAfter = $neooNetSocial - $neooMontantPas - $neooAcompte;
        $neooRemboursementsNonImposables = $neooExpenseTotal + $neooIk + $neooCpIndemnite;
        $neooNetFinalPayer = $neooNetSocialAfter + $neooRemboursementsNonImposables;
        $neooCommissionVariable = $neooNetSocial * ($neooUrssaf / 100);
        $neooTotalCommission = $neooFixed + $neooCommissionVariable;
        $neooReversementBrut = $neooTotalIn - $neooTotalCommission;
        return [
            'neooTotalIn' => $neooTotalIn,
            'neooFixed' => $neooFixed,
            'neooExpenseGroups' => $neooExpenseGroups,
            'neooExpenseTotal' => $neooExpenseTotal,
            'neooVatRate' => $neooVatRate,
            'neooVatRecoverable' => $neooVatRecoverable,
            'neooC27' => $neooC27,
            'neooKm' => $neooKm,
            'neooFraisKm' => $neooFraisKm,
            'neooIk' => $neooIk,
            'neooCpTaux' => $neooCpTaux,
            'neooCpIndemnite' => $neooCpIndemnite,
            'neooUrssaf' => $neooUrssaf,
            'neooNetSocial' => $neooNetSocial,
            'neooNetSocialNumerator' => $neooNetSocialNumerator,
            'neooSalaireBrutCotisations' => $neooSalaireBrutCotisations,
            'neooTauxPas' => $neooTauxPas,
            'neooMontantPas' => $neooMontantPas,
            'neooAcompte' => $neooAcompte,
            'neooNetSocialAfter' => $neooNetSocialAfter,
            'neooRemboursementsNonImposables' => $neooRemboursementsNonImposables,
            'neooNetFinalPayer' => $neooNetFinalPayer,
            'neooCommissionVariable' => $neooCommissionVariable,
            'neooTotalCommission' => $neooTotalCommission,
            'neooReversementBrut' => $neooReversementBrut,
        ];
    }
}
