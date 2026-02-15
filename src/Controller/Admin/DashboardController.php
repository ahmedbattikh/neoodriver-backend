<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Option\EA;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use App\Entity\Driver;
use App\Entity\Attachment;
use App\Entity\PaymentOperation;
use App\Entity\PaymentBatch;
use App\Controller\Admin\DriverIntegrationCrudController;
use App\Entity\Goals;
use App\Service\Storage\R2Client;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use App\Entity\DriverIntegrationAccount;
use App\Entity\DriverIntegration;
use App\Service\BoltService;
use App\Enum\PaymentMethodType;
use Psr\Log\LoggerInterface;
use App\Entity\ExpenseNote;
use App\Entity\NeooConfig;
use App\Entity\NeooFee;
use App\Entity\Balance;
use App\Entity\AdvanceRequest;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
#[IsGranted('ROLE_SUPER_ADMIN')]
final class DashboardController extends AbstractDashboardController
{
    public function __construct(private readonly EntityManagerInterface $em, private readonly R2Client $r2, private readonly MailerInterface $mailer, private readonly BoltService $bolt, private readonly ?LoggerInterface $logger = null) {}
    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Administration')
            ->setFaviconPath('favicon.ico');
    }

    public function configureAssets(): Assets
    {
        return Assets::new()
            ->addCssFile('css/admin-users.css');
    }

    // Optional: show icons in the main menu using Font Awesome (loaded by layout override)
    // Add more menu items as needed.
    // use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
    public function configureMenuItems(): iterable
    {
        yield \EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem::linkToDashboard('Dashboard', 'fas fa-home');
        yield \EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem::linkToRoute('Users', 'fas fa-users', 'admin_users_list');
        yield \EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem::linkToRoute('Unverified Users', 'fas fa-user-times', 'admin_users_unverified');
        yield \EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem::subMenu('Configuration', 'fas fa-cog')->setSubItems([
            \EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem::linkToCrud('Integration', 'fas fa-plug', \App\Entity\DriverIntegration::class),
            \EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem::linkToCrud('Goals', 'fas fa-bullseye', Goals::class),
            \EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem::linkToCrud('Neoo Config', 'fas fa-sliders-h', NeooConfig::class),
            \EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem::linkToCrud('Neoo Fees', 'fas fa-percent', \App\Entity\NeooFee::class),
        ]);
    }



    #[Route('/admin/users', name: 'admin_users_list', defaults: [EA::DASHBOARD_CONTROLLER_FQCN => self::class])]
    public function users(): Response
    {
        $users = $this->em->getRepository(User::class)->findBy([], ['id' => 'DESC']);
        $avatars = [];
        foreach ($users as $u) {
            $pic = $u->getPicProfile();
            if ($pic instanceof Attachment && $pic->getId() !== null) {
                $avatars[$u->getId()] = $this->r2->getSignedUrl($pic->getFilePath(), 900);
            }
        }
        return $this->render('admin/users_list.html.twig', [
            'users' => $users,
            'avatars' => $avatars,
        ]);
    }

    #[Route('/admin/users/unverified', name: 'admin_users_unverified', defaults: [EA::DASHBOARD_CONTROLLER_FQCN => self::class])]
    public function usersUnverified(): Response
    {
        $users = $this->em->getRepository(User::class)->findBy(['verified' => false], ['id' => 'DESC']);
        return $this->render('admin/users_list.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/admin/users/{id}', name: 'admin_user_show', methods: ['GET'], requirements: ['id' => '\\d+'], defaults: [EA::DASHBOARD_CONTROLLER_FQCN => self::class])]
    public function userShow(Request $request, int $id): Response
    {
        $user = $this->em->getRepository(User::class)->find($id);
        if (!$user instanceof User) {
            return $this->redirectToRoute('admin_users_list');
        }
        $attachmentUrls = [];
        $add = function ($att) use (&$attachmentUrls) {
            if ($att instanceof Attachment && $att->getId() !== null) {
                $attachmentUrls[$att->getId()] = $this->r2->getSignedUrl($att->getFilePath(), 900);
            }
        };
        $add($user->getPicProfile());
        $driver = null;
        try {
            $driver = $user->getDriverProfile();
        } catch (\Throwable $e) {
            if ($this->logger) {
                $this->logger->error('admin_user_show_driver_load_failed', [
                    'user_id' => $user->getId(),
                    'error' => $e->getMessage(),
                ]);
            }
            $this->addFlash('error', 'Driver data unavailable. Please ensure database migrations are applied.');
        }
        $operations = [];
        $batches = [];
        $integrationCodes = [];
        $opsPage = max(1, (int) $request->query->get('ops_page', 1));
        $opsSize = max(1, min(100, (int) $request->query->get('ops_size', 20)));
        $opsOffset = ($opsPage - 1) * $opsSize;
        $notesPage = max(1, (int) $request->query->get('notes_page', 1));
        $notesSize = max(1, min(100, (int) $request->query->get('notes_size', 20)));
        $notesOffset = ($notesPage - 1) * $notesSize;
        $notesFilter = (string) $request->query->get('notes_filter', 'all');
        $notesWeek = (int) $request->query->get('notes_week', 0);
        $notesWeekStart = new \DateTimeImmutable('monday this week 00:00:00');
        if ($notesWeek !== 0) {
            $days = $notesWeek * 7;
            $notesWeekStart = $notesWeekStart->modify(($days >= 0 ? '+' : '') . $days . ' days');
        }
        $notesWeekEnd = $notesWeekStart->modify('+7 days');
        $batchesPage = max(1, (int) $request->query->get('batches_page', 1));
        $batchesSize = max(1, min(100, (int) $request->query->get('batches_size', 20)));
        $batchesOffset = ($batchesPage - 1) * $batchesSize;
        $activeTab = (string) $request->query->get('tab', 'info');
        $neooStart = new \DateTimeImmutable('first day of this month 00:00:00');
        $neooEnd = new \DateTimeImmutable('first day of next month 00:00:00');
        $neooStartParam = (string) $request->query->get('neoo_start', '');
        $neooEndParam = (string) $request->query->get('neoo_end', '');
        try {
            if ($neooStartParam !== '') {
                $neooStart = new \DateTimeImmutable($neooStartParam . ' 00:00:00');
            }
        } catch (\Throwable) {
        }
        try {
            if ($neooEndParam !== '') {
                $neooEnd = new \DateTimeImmutable($neooEndParam . ' 00:00:00');
            }
        } catch (\Throwable) {
        }
        $neooEndQuery = $neooEndParam !== '' ? $neooEnd->modify('+1 day') : $neooEnd;
        $weekIn = 0.0;
        $weekOut = 0.0;
        $weekNet = 0.0;
        $weekStart = new \DateTimeImmutable('monday this week 00:00:00');
        $weekEnd = new \DateTimeImmutable('monday next week 00:00:00');
        $weekCurrency = null;
        $integrationAccounts = [];
        $enabledIntegrations = [];
        $expenseNotes = [];
        $notesTotalPages = 1;
        $notesCount = 0;
        $neooTotalIn = 0.0;
        $neooFixed = 0.0;
        $neooExpenseGroups = [];
        $neooExpenseTotal = 0.0;
        $neooDebug = 1;
        $neooDebugData = [];
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
        $balanceSold = 0.0;
        $balanceTotalDebit = 0.0;
        $balanceSoldConge = 0.0;
        $balanceLastUpdate = null;
        if ($driver instanceof Driver) {
            if ($neooStartParam === '' && $neooEndParam === '') {
                try {
                    $latestNote = $this->em->createQueryBuilder()
                        ->select('n')
                        ->from(ExpenseNote::class, 'n')
                        ->where('n.driver = :driver')
                        ->orderBy('n.noteDate', 'DESC')
                        ->setMaxResults(1)
                        ->setParameter('driver', $driver)
                        ->getQuery()
                        ->getOneOrNullResult();
                    if ($latestNote instanceof ExpenseNote) {
                        $d = $latestNote->getNoteDate();
                        $neooStart = new \DateTimeImmutable($d->format('Y-m-01 00:00:00'));
                        $neooEnd = $neooStart->modify('+1 month');
                    }
                } catch (\Throwable) {
                }
            }
            $docs = $driver->getDocuments();
            if ($docs) {
                $add($docs->getIdentityPhoto());
                $add($docs->getVtcCardFront());
                $add($docs->getVtcCardBack());
                $add($docs->getDrivingLicenseFront());
                $add($docs->getDrivingLicenseBack());
                $add($docs->getIdentityCardFront());
                $add($docs->getIdentityCardBack());
                $add($docs->getHealthCard());
                $add($docs->getBankStatement());
                $add($docs->getProofOfResidence());
                $add($docs->getSecureDrivingRightCertificate());
            }
            $cdocs = $driver->getCompanyDocuments();
            if ($cdocs) {
                $add($cdocs->getEmploymentContract());
                $add($cdocs->getEmployerCertificate());
                $add($cdocs->getPreEmploymentDeclaration());
                $add($cdocs->getMutualInsuranceCertificate());
                $add($cdocs->getUrssafComplianceCertificate());
                $add($cdocs->getKbisExtract());
                $add($cdocs->getRevtcRegistrationCertificate());
            }
            foreach ($driver->getVehicles() as $v) {
                $add($v->getRegistrationCertificate());
                $add($v->getPaidTransportInsuranceCertificate());
                $add($v->getTechnicalInspection());
                $add($v->getVehicleFrontPhoto());
                $add($v->getInsuranceNote());
            }
            try {
                $opRepo = $this->em->getRepository(PaymentOperation::class);
                $opsCount = $opRepo->count(['driver' => $driver]);
                $operations = $opRepo->findBy(['driver' => $driver], ['occurredAt' => 'DESC'], $opsSize, $opsOffset);
                $codesRows = $this->em->createQueryBuilder()
                    ->select('DISTINCT o.integrationCode')
                    ->from(PaymentOperation::class, 'o')
                    ->where('o.driver = :driver')
                    ->setParameter('driver', $driver)
                    ->getQuery()
                    ->getArrayResult();
                foreach ($codesRows as $row) {
                    $integrationCodes[$row['integrationCode']] = true;
                }
                $batchesAll = [];
                foreach (array_keys($integrationCodes) as $code) {
                    $integration = $this->em->getRepository(\App\Entity\DriverIntegration::class)->findOneBy(['code' => $code]);
                    if ($integration instanceof \App\Entity\DriverIntegration) {
                        $list = $this->em->getRepository(PaymentBatch::class)->findBy(['integration' => $integration], ['periodStart' => 'DESC']);
                        foreach ($list as $b) {
                            $batchesAll[] = $b;
                        }
                    }
                }
                usort($batchesAll, function ($a, $b) {
                    return $a->getPeriodStart() < $b->getPeriodStart() ? 1 : -1;
                });
                $batchesCount = count($batchesAll);
                $batches = array_slice($batchesAll, $batchesOffset, $batchesSize);
                $opsTotalPages = max(1, (int) ceil($opsCount / $opsSize));
                $batchesTotalPages = max(1, (int) ceil($batchesCount / $batchesSize));
                $totalsRow = $this->em->createQueryBuilder()
                    ->select("SUM(CASE WHEN LOWER(o.direction) IN ('in','credit') THEN o.amount ELSE 0 END) AS totalIn")
                    ->addSelect("SUM(CASE WHEN LOWER(o.direction) IN ('out','debit') THEN o.amount ELSE 0 END) AS totalOut")
                    ->from(PaymentOperation::class, 'o')
                    ->where('o.driver = :driver')
                    ->andWhere('o.occurredAt >= :start')
                    ->andWhere('o.occurredAt < :end')
                    ->setParameter('driver', $driver)
                    ->setParameter('start', $weekStart)
                    ->setParameter('end', $weekEnd)
                    ->getQuery()
                    ->getOneOrNullResult();
                $weekIn = (float) (($totalsRow['totalIn'] ?? 0) ?: 0);
                $weekOut = (float) (($totalsRow['totalOut'] ?? 0) ?: 0);
                $weekNet = $weekIn - $weekOut;
                $weekCurrency = null;
                $weekOp = $this->em->createQueryBuilder()
                    ->select('o')
                    ->from(PaymentOperation::class, 'o')
                    ->where('o.driver = :driver')
                    ->andWhere('o.occurredAt >= :start')
                    ->andWhere('o.occurredAt < :end')
                    ->orderBy('o.occurredAt', 'DESC')
                    ->setMaxResults(1)
                    ->setParameter('driver', $driver)
                    ->setParameter('start', $weekStart)
                    ->setParameter('end', $weekEnd)
                    ->getQuery()
                    ->getOneOrNullResult();
                if ($weekOp instanceof PaymentOperation) {
                    $weekCurrency = $weekOp->getCurrency();
                }
            } catch (\Throwable $e) {
                if ($this->logger) {
                    $this->logger->error('admin_user_show_operations_failed', [
                        'user_id' => $user->getId(),
                        'driver_id' => $driver->getId(),
                        'error' => $e->getMessage(),
                    ]);
                }
                $this->addFlash('warning', 'Operations unavailable. Please ensure database migrations are applied.');
            }
            $integrationAccounts = $driver->getIntegrationAccounts()->toArray();
            $enabledIntegrations = $this->em->getRepository(DriverIntegration::class)->findBy(['enabled' => true], ['name' => 'ASC']);
            try {
                if (strtolower($notesFilter) === 'week') {
                    $qb = $this->em->createQueryBuilder()
                        ->select('n')
                        ->from(ExpenseNote::class, 'n')
                        ->where('n.driver = :driver')
                        ->andWhere('n.noteDate >= :start')
                        ->andWhere('n.noteDate < :end')
                        ->setParameter('driver', $driver)
                        ->setParameter('start', $notesWeekStart)
                        ->setParameter('end', $notesWeekEnd)
                        ->orderBy('n.noteDate', 'DESC')
                        ->setFirstResult($notesOffset)
                        ->setMaxResults($notesSize);
                    $expenseNotes = $qb->getQuery()->getResult();
                    $notesCount = (int) $this->em->createQueryBuilder()
                        ->select('COUNT(n.id)')
                        ->from(ExpenseNote::class, 'n')
                        ->where('n.driver = :driver')
                        ->andWhere('n.noteDate >= :start')
                        ->andWhere('n.noteDate < :end')
                        ->setParameter('driver', $driver)
                        ->setParameter('start', $notesWeekStart)
                        ->setParameter('end', $notesWeekEnd)
                        ->getQuery()
                        ->getSingleScalarResult();
                } else {
                    $notesRepo = $this->em->getRepository(ExpenseNote::class);
                    $notesCount = $notesRepo->count(['driver' => $driver]);
                    $expenseNotes = $notesRepo->findBy(['driver' => $driver], ['noteDate' => 'DESC'], $notesSize, $notesOffset);
                }
                $notesTotalPages = max(1, (int) ceil($notesCount / $notesSize));
                foreach ($expenseNotes as $n) {
                    if ($n instanceof ExpenseNote) {
                        $add($n->getInvoice());
                    }
                }
            } catch (\Throwable $e) {
                if ($this->logger) {
                    $this->logger->error('admin_user_show_expense_notes_failed', [
                        'user_id' => $user->getId(),
                        'driver_id' => $driver->getId(),
                        'error' => $e->getMessage(),
                    ]);
                }
                try {
                    $notesRepo = $this->em->getRepository(ExpenseNote::class);
                    $notesCount = $notesRepo->count(['driver' => $driver]);
                    $expenseNotes = $notesRepo->findBy(['driver' => $driver], ['noteDate' => 'DESC'], $notesSize, $notesOffset);
                    $notesTotalPages = max(1, (int) ceil($notesCount / $notesSize));
                    foreach ($expenseNotes as $n) {
                        if ($n instanceof ExpenseNote) {
                            $add($n->getInvoice());
                        }
                    }
                } catch (\Throwable $e2) {
                    $this->addFlash('warning', 'Expense notes unavailable (' . $e2->getMessage() . ')');
                }
            }
            try {
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
                $neooFixed = 0.0;
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
                $neooExpenseGroups = [];
                $neooExpenseTotal = 0.0;
                $neooGroupsFromItems = false;
                $neooExpenseSource = 'noteDate';
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
                    $neooGroupsFromItems = true;
                    $neooExpenseSource = 'noteDate_items';
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
                    $neooExpenseSource = 'createdAt';
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
                    $neooGroupsFromItems = true;
                    $neooExpenseSource = 'createdAt_items';
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
                    ->setParameter('cash', \App\Enum\PaymentMethodType::CASH)
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
                if ($neooDebug) {
                    $neooDebugData = [
                        'items' => $items,
                        'start' => $neooStart->format('Y-m-d'),
                        'end' => $neooEnd->format('Y-m-d'),
                        'groups' => $neooExpenseGroups,
                        'total' => $neooExpenseTotal,
                        'fallbackUsed' => $neooGroupsFromItems,
                        'source' => $neooExpenseSource,
                    ];
                }
            } catch (\Throwable $e) {
                if ($this->logger) {
                    $this->logger->error('admin_user_show_neoo_failed', [
                        'user_id' => $user->getId(),
                        'driver_id' => $driver->getId(),
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            try {
                $balance = $this->em->getRepository(Balance::class)->findOneBy(['driver' => $driver]);
                if (!$balance instanceof Balance) {
                    $balance = new Balance();
                    $balance->setDriver($driver);
                    $this->em->persist($balance);
                    $this->em->flush();
                }
                $calc = (array) ($this->em->createQueryBuilder()
                    ->select('SUM(CASE WHEN LOWER(o.direction) IN (\'in\',\'credit\') AND o.paymentMethod <> :cash THEN o.amount ELSE 0 END) AS nonCashCredits')
                    ->addSelect('SUM(CASE WHEN LOWER(o.direction) IN (\'out\',\'debit\') OR o.paymentMethod = :cash THEN o.amount ELSE 0 END) AS totalDebits')
                    ->from(PaymentOperation::class, 'o')
                    ->where('o.driver = :driver')
                    ->setParameter('driver', $driver)
                    ->setParameter('cash', \App\Enum\PaymentMethodType::CASH)
                    ->getQuery()
                    ->getOneOrNullResult() ?? []);
                $calcSold = (float) ((($calc['nonCashCredits'] ?? 0) ?: 0) - ((($calc['totalDebits'] ?? 0) ?: 0)));
                $calcDebit = (float) (($calc['totalDebits'] ?? 0) ?: 0);
                if (abs((float)$balance->getSold() - $calcSold) > 0.0001 || abs((float)$balance->getTotalDebit() - $calcDebit) > 0.0001) {
                    $balance->setSold(number_format($calcSold, 3, '.', ''));
                    $balance->setTotalDebit(number_format($calcDebit, 3, '.', ''));
                    $balance->setLastUpdate(new \DateTimeImmutable('now'));
                    $this->em->flush();
                }
                $balanceSold = (float) $balance->getSold();
                $balanceTotalDebit = (float) $balance->getTotalDebit();
                $balanceSoldConge = (float) $balance->getSoldConge();
                $balanceLastUpdate = $balance->getLastUpdate();
            } catch (\Throwable $e) {
            }
        }
        $validationChoices = [
            'VALIDATION_INPROGRESS',
            'DOCUMENT_INVALIDE',
            'DOCUMENT_VALID',
            'DOCUMENT_REJECTED',
        ];
        $advanceRequests = [];
        if ($driver instanceof Driver) {
            try {
                $advanceRequests = $this->em->getRepository(AdvanceRequest::class)->findBy(['driver' => $driver], ['createdAt' => 'DESC']);
            } catch (\Throwable) {
                $advanceRequests = [];
            }
            foreach ($advanceRequests as $r) {
                if ($r instanceof AdvanceRequest) {
                    $add($r->getAttachment());
                }
            }
        }
        return $this->render('admin/user_show.html.twig', [
            'user' => $user,
            'attachmentUrls' => $attachmentUrls,
            'validationChoices' => $validationChoices,
            'operations' => $operations,
            'batches' => $batches,
            'opsPage' => $opsPage,
            'opsSize' => $opsSize,
            'opsTotalPages' => $opsTotalPages ?? 1,
            'batchesPage' => $batchesPage,
            'batchesSize' => $batchesSize,
            'batchesTotalPages' => $batchesTotalPages ?? 1,
            'activeTab' => $activeTab,
            'weekIn' => $weekIn,
            'weekOut' => $weekOut,
            'weekNet' => $weekNet,
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'weekCurrency' => $weekCurrency,
            'integrationAccounts' => $integrationAccounts,
            'enabledIntegrations' => $enabledIntegrations,
            'expenseNotes' => $expenseNotes,
            'notesPage' => $notesPage,
            'notesSize' => $notesSize,
            'notesTotalPages' => $notesTotalPages,
            'notesFilter' => $notesFilter,
            'notesWeek' => $notesWeek,
            'notesWeekStart' => $notesWeekStart,
            'notesWeekEnd' => $notesWeekEnd,
            'neooStart' => $neooStart,
            'neooEnd' => $neooEnd,
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
            'neooDebug' => $neooDebug,
            'neooDebugData' => $neooDebugData,
            'balanceSold' => $balanceSold,
            'balanceTotalDebit' => $balanceTotalDebit,
            'balanceSoldConge' => $balanceSoldConge,
            'balanceLastUpdate' => $balanceLastUpdate,
            'advanceRequests' => $advanceRequests,
        ]);
    }

    #[Route('/admin/users/{id}/edit', name: 'admin_user_edit', methods: ['GET', 'POST'], requirements: ['id' => '\\d+'], defaults: [EA::DASHBOARD_CONTROLLER_FQCN => self::class])]
    public function userEdit(Request $request, int $id): Response
    {
        $user = $this->em->getRepository(User::class)->find($id);
        if (!$user instanceof User) {
            return $this->redirectToRoute('admin_users_list');
        }
        $form = $this->createFormBuilder($user)
            ->add('email', EmailType::class)
            ->add('firstName', TextType::class, ['required' => false])
            ->add('lastName', TextType::class, ['required' => false])
            ->add('mobileNumber', TextType::class, ['required' => false])
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();
            return $this->redirectToRoute('admin_users_list');
        }
        return $this->render('admin/user_show.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/users/{id}/integration-accounts', name: 'admin_user_integration_account_add', methods: ['POST'], requirements: ['id' => '\\d+'])]
    public function addIntegrationAccount(Request $request, int $id): Response
    {
        $user = $this->em->getRepository(User::class)->find($id);
        if (!$user instanceof User) {
            return $this->redirectToRoute('admin_users_list');
        }
        $driver = $user->getDriverProfile();
        if (!$driver instanceof Driver) {
            $driver = new Driver();
            $driver->setUser($user);
            $this->em->persist($driver);
            $this->em->flush();
        }
        $token = (string) $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid('add_integration_account_' . $user->getId(), $token)) {
            return $this->redirectToRoute('admin_user_show', ['id' => $user->getId(), 'tab' => 'integrations']);
        }
        $integrationId = (int) $request->request->get('integrationId', 0);
        $idDriver = trim((string) $request->request->get('idDriver', ''));
        $integration = $this->em->getRepository(DriverIntegration::class)->find($integrationId);
        if ($integration instanceof DriverIntegration && $idDriver !== '') {
            $acc = new DriverIntegrationAccount();
            $acc->setDriver($driver);
            $acc->setIntegration($integration);
            $acc->setIdDriver($idDriver);
            $this->em->persist($acc);
            $this->em->flush();
        }
        return $this->redirectToRoute('admin_user_show', ['id' => $user->getId(), 'tab' => 'integrations']);
    }

    #[Route('/admin/users/{id}/integration-accounts/{accId}/sync', name: 'admin_user_integration_account_sync', methods: ['POST'], requirements: ['id' => '\\d+', 'accId' => '\\d+'])]
    public function syncIntegrationAccount(Request $request, int $id, int $accId): Response
    {
        $debug = ((string) $request->request->get('debug', '0') === '1');
        $user = $this->em->getRepository(User::class)->find($id);
        if (!$user instanceof User) {
            return $this->redirectToRoute('admin_users_list');
        }
        $driver = $user->getDriverProfile();
        if (!$driver instanceof Driver) {
            return $this->redirectToRoute('admin_user_show', ['id' => $user->getId(), 'tab' => 'integrations']);
        }
        $token = (string) $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid('sync_integration_account_' . $user->getId() . '_' . $accId, $token)) {
            return $this->redirectToRoute('admin_user_show', ['id' => $user->getId(), 'tab' => 'integrations']);
        }
        $acc = $this->em->getRepository(DriverIntegrationAccount::class)->find($accId);
        if (!$acc instanceof DriverIntegrationAccount || $acc->getDriver()->getId() !== $driver->getId()) {
            return $this->redirectToRoute('admin_user_show', ['id' => $user->getId(), 'tab' => 'integrations']);
        }
        $integration = $acc->getIntegration();
        if (!$integration instanceof DriverIntegration || !$integration->isEnabled()) {
            return $this->redirectToRoute('admin_user_show', ['id' => $user->getId(), 'tab' => 'integrations']);
        }
        try {
            $cid = $integration->getBoltCustomerId();
            $secret = $integration->getBoltCustomerSecret();
            $scope = $integration->getBoltScope() ?? 'fleet-integration:api';
            $tokenPayload = ($cid && $secret) ? $this->bolt->getTokenUsing($cid, $secret, $scope) : $this->bolt->getToken();

            $accessToken = (string) ($tokenPayload['access_token'] ?? '');
            if ($this->logger) {
                $this->logger->info('bolt_token_retrieved', [
                    'integration_code' => $integration->getCode(),
                    'has_access_token' => $accessToken !== '',
                ]);
            }
            if ($accessToken === '') {
                $this->addFlash('warning', 'Bolt authentication failed for ' . $integration->getName());
                return $this->redirectToRoute('admin_user_show', ['id' => $user->getId(), 'tab' => 'integrations']);
            }
            $now = time();
            $startTs = 1767398400;
            $endTs = 1769904000;
            $companyIds = $integration->getBoltCompanyIds();
            $ordersPayload = $this->bolt->getFleetOrders($accessToken, 0, 1000, $companyIds, $startTs, $endTs, 'price_review');
            if ($debug) {
                if ($this->logger) {
                    $this->logger->info('bolt_orders_payload', ['payload' => $ordersPayload]);
                }
                $snippet = substr(json_encode($ordersPayload, JSON_UNESCAPED_SLASHES), 0, 2000);
                $this->addFlash('info', 'Bolt orders payload (truncated): ' . $snippet);
            }

            $orders = [];
            if (isset($ordersPayload['data'])) {
                if (isset($ordersPayload['data']['orders']) && is_array($ordersPayload['data']['orders'])) {
                    $orders = $ordersPayload['data']['orders'];
                } elseif (is_array($ordersPayload['data'])) {
                    foreach ($ordersPayload['data'] as $item) {
                        if (is_array($item) && isset($item['orders']) && is_array($item['orders'])) {
                            $orders = array_merge($orders, $item['orders']);
                        }
                    }
                }
            }
            if ($this->logger) {
                $this->logger->info('bolt_orders_received', [
                    'integration_code' => $integration->getCode(),
                    'orders_count' => is_countable($orders) ? count($orders) : 0,
                ]);
            }
            $targetDriverUuid = (string) $acc->getIdDriver();
            $opRepo = $this->em->getRepository(PaymentOperation::class);
            $processed = 0;
            foreach ($orders as $ord) {
                $uuid = (string) ($ord['driver_uuid'] ?? '');
                if ($uuid === '' || strcasecmp($uuid, $targetDriverUuid) !== 0) {
                    continue;
                }
                $ref = (string) ($ord['order_reference'] ?? '');
                $price = is_array($ord['order_price'] ?? null) ? $ord['order_price'] : [];
                $net = (float) ($price['net_earnings'] ?? 0);
                $tip = (float) ($price['tip'] ?? 0);
                $bonus = (float) (($price['in_app_discount'] ?? 0) + ($price['cash_discount'] ?? 0));
                $methodRaw = strtolower((string) ($ord['payment_method'] ?? ''));
                $pmEnum = $methodRaw === 'cash' ? PaymentMethodType::CASH : PaymentMethodType::CB;
                $ts = (int) ($ord['payment_confirmed_timestamp'] ?? $ord['order_finished_timestamp'] ?? $ord['order_drop_off_timestamp'] ?? $ord['order_created_timestamp'] ?? time());
                $occurredAt = (new \DateTimeImmutable('@' . $ts))->setTimezone(new \DateTimeZone('UTC'));
                $status = strtoupper((string) ($ord['order_status'] ?? 'FINISHED'));
                $rideDistance = (float) ($ord['ride_distance'] / 1000 ?? 0);
                $existing = null;
                if ($ref !== '') {
                    $existing = $opRepo->findOneBy([
                        'driver' => $driver,
                        'integrationCode' => $integration->getCode(),
                        'externalReference' => $ref,
                    ]);
                }
                if ($existing instanceof PaymentOperation) {
                    $existing->setOperationType('ORDER');
                    $existing->setDirection('IN');
                    $existing->setAmount(number_format($net, 3, '.', ''));
                    $existing->setCurrency('EUR');
                    $existing->setStatus($status);
                    $existing->setDescription(null);
                    $existing->setOriginalObject(is_array($ord) ? $ord : null);
                    $existing->setPaymentMethodEnum($pmEnum);
                    $existing->setTips(number_format($tip, 3, '.', ''));
                    $existing->setBonus(number_format($bonus, 3, '.', ''));
                    $existing->setOccurredAt($occurredAt);
                    $existing->setRideDistance(number_format($rideDistance, 3, '.', ''));
                    $processed++;
                } else {
                    $op = new PaymentOperation();
                    $op->setDriver($driver);
                    $op->setIntegrationCode($integration->getCode());
                    $op->setOperationType('ORDER');
                    $op->setDirection('IN');
                    $op->setAmount(number_format($net, 3, '.', ''));
                    $op->setCurrency('EUR');
                    $op->setStatus($status);
                    $op->setExternalReference($ref !== '' ? $ref : null);
                    $op->setDescription(null);
                    $op->setOriginalObject(is_array($ord) ? $ord : null);
                    $op->setPaymentMethodEnum($pmEnum);
                    $op->setTips(number_format($tip, 3, '.', ''));
                    $op->setBonus(number_format($bonus, 3, '.', ''));
                    $op->setOccurredAt($occurredAt);
                    $op->setRideDistance(number_format($rideDistance, 3, '.', ''));
                    $this->em->persist($op);
                    $processed++;
                }
            }
            $this->em->flush();
            $this->addFlash('info', 'Synced ' . $processed . ' operations for ' . $integration->getName());
        } catch (\Throwable $e) {
            if ($this->logger) {
                $this->logger->error('integration_sync_failed', [
                    'integration_code' => $integration->getCode(),
                    'error' => $e->getMessage(),
                ]);
            }
            $this->addFlash('error', 'Sync failed: ' . $e->getMessage());
            if ($debug) {
                $this->addFlash('error', 'Bolt debug error: ' . $e->getMessage());
            }
        }
        return $this->redirectToRoute('admin_user_show', ['id' => $user->getId(), 'tab' => 'integrations']);
    }
    #[Route('/admin/users/{id}/docs/valid', name: 'admin_user_doc_valid', methods: ['POST'], requirements: ['id' => '\\d+'])]
    public function userUpdateValidation(Request $request, int $id): Response
    {
        $user = $this->em->getRepository(User::class)->find($id);
        if (!$user instanceof User) {
            return $this->redirectToRoute('admin_users_list');
        }
        $field = (string) $request->request->get('field', '');
        $value = (string) $request->request->get('value', '');
        $token = (string) $request->request->get('_token', '');

        $allowed = [
            'vtcCardValid',
            'drivingLicenseValid',
            'identityCardValid',
            'healthCardValid',
            'bankStatementValid',
            'proofOfResidenceValid',
            'secureDrivingRightCertificateValid',
        ];
        if (!in_array($field, $allowed, true)) {
            return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
        }
        if (!$this->isCsrfTokenValid('update_validation_' . $user->getId() . '_' . $field, $token)) {
            return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
        }

        $driver = $user->getDriverProfile();
        if (!$driver instanceof Driver) {
            return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
        }
        $docs = $driver->getDocuments();
        if (!$docs instanceof \App\Entity\DriverDocuments) {
            return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
        }

        $status = \App\Enum\ValidationStatus::tryFrom($value) ?? \App\Enum\ValidationStatus::VALIDATION_INPROGRESS;
        $setter = 'set' . ucfirst($field);
        if (is_callable([$docs, $setter])) {
            $docs->$setter($status);
            $this->em->flush();
        }
        return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
    }

    #[Route('/admin/users/{id}/toggle/{active}', name: 'admin_user_toggle', requirements: ['active' => '\\d+'], methods: ['GET'])]
    public function userToggle(int $id, int $active): Response
    {
        $user = $this->em->getRepository(User::class)->find($id);
        if ($user instanceof User) {
            $driver = $user->getDriverProfile();
            if (!$driver instanceof Driver) {
                $driver = new Driver();
                $driver->setUser($user);
                $this->em->persist($driver);
            }
            $driver->setActive($active === 1);
            $this->em->flush();
        }
        return $this->redirectToRoute('admin_users_list');
    }

    #[Route('/admin/expense-notes/{id}/valide', name: 'admin_expense_note_valide', methods: ['POST'], requirements: ['id' => '\\d+'])]
    public function setExpenseNoteValide(Request $request, int $id): Response
    {
        $note = $this->em->getRepository(ExpenseNote::class)->find($id);
        if (!$note instanceof ExpenseNote) {
            return $this->redirectToRoute('admin_users_list');
        }
        $token = (string) $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid('set_expense_note_valide_' . $note->getId(), $token)) {
            $driver = $note->getDriver();
            return $this->redirectToRoute('admin_user_show', ['id' => $driver->getUser()->getId(), 'tab' => 'expenses']);
        }
        $val = $request->request->get('valide', null);
        $newValide = null;
        if ($val === '1' || $val === 1) {
            $newValide = true;
        } elseif ($val === '0' || $val === 0) {
            $newValide = false;
        }
        $note->setValide($newValide);
        $this->em->flush();
        $driver = $note->getDriver();
        return $this->redirectToRoute('admin_user_show', [
            'id' => $driver->getUser()->getId(),
            'tab' => 'expenses',
        ]);
    }

    #[Route('/admin/users/{id}/verify', name: 'admin_user_verify', methods: ['POST'], requirements: ['id' => '\\d+'])]
    public function userVerify(Request $request, int $id): Response
    {
        $user = $this->em->getRepository(User::class)->find($id);
        if (!$user instanceof User) {
            return $this->redirectToRoute('admin_users_list');
        }
        $token = (string) $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid('verify_user_' . $user->getId(), $token)) {
            return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
        }
        $val = (string) $request->request->get('val', '0');
        $user->setVerified($val === '1');
        $this->em->flush();
        return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
    }

    #[Route('/admin/users/{id}/activate-notify', name: 'admin_user_activate_notify', methods: ['POST'], requirements: ['id' => '\\d+'])]
    public function activateAndNotify(Request $request, int $id): Response
    {
        $user = $this->em->getRepository(User::class)->find($id);
        if (!$user instanceof User) {
            return $this->redirectToRoute('admin_users_list');
        }
        $token = (string) $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid('activate_notify_' . $user->getId(), $token)) {
            return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
        }
        $user->setVerified(true);
        $driver = $user->getDriverProfile();
        $lines = [];
        if ($driver instanceof Driver) {
            $docs = $driver->getDocuments();
            if ($docs) {
                $lines[] = 'VTC Card: ' . $docs->getVtcCardValid()->value;
                $lines[] = 'Driving License: ' . $docs->getDrivingLicenseValid()->value;
                $lines[] = 'Identity Card: ' . $docs->getIdentityCardValid()->value;
                $lines[] = 'Health Card: ' . $docs->getHealthCardValid()->value;
                $lines[] = 'Bank Statement: ' . $docs->getBankStatementValid()->value;
                $lines[] = 'Proof of Residence: ' . $docs->getProofOfResidenceValid()->value;
                $lines[] = 'Secure Driving Right Certificate: ' . $docs->getSecureDrivingRightCertificateValid()->value;
            }
        }
        $email = (new TemplatedEmail())
            ->from('no-reply@neoodriver.test')
            ->to((string) $user->getEmail())
            ->subject('Activation de compte')
            ->htmlTemplate('email/activation.html.twig')
            ->textTemplate('email/activation.txt.twig')
            ->context(['user' => $user, 'lines' => $lines]);
        $this->mailer->send($email);
        $this->em->flush();
        return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
    }

    #[Route('/admin/users/{id}/notify-docs', name: 'admin_user_notify_docs', methods: ['POST'], requirements: ['id' => '\\d+'])]
    public function notifyDocs(Request $request, int $id): Response
    {
        $user = $this->em->getRepository(User::class)->find($id);
        if (!$user instanceof User) {
            return $this->redirectToRoute('admin_users_list');
        }
        $token = (string) $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid('notify_docs_' . $user->getId(), $token)) {
            return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
        }
        $driver = $user->getDriverProfile();
        $lines = [];
        if ($driver instanceof Driver) {
            $docs = $driver->getDocuments();
            if ($docs) {
                $lines[] = 'VTC Card: ' . $docs->getVtcCardValid()->value;
                $lines[] = 'Driving License: ' . $docs->getDrivingLicenseValid()->value;
                $lines[] = 'Identity Card: ' . $docs->getIdentityCardValid()->value;
                $lines[] = 'Health Card: ' . $docs->getHealthCardValid()->value;
                $lines[] = 'Bank Statement: ' . $docs->getBankStatementValid()->value;
                $lines[] = 'Proof of Residence: ' . $docs->getProofOfResidenceValid()->value;
                $lines[] = 'Secure Driving Right Certificate: ' . $docs->getSecureDrivingRightCertificateValid()->value;
            }
        }
        $text = 'Statut de vos documents:';
        if ($lines !== []) {
            $text .= "\n" . implode("\n", $lines);
        }
        $html = '<p>Statut de vos documents:</p>';
        if ($lines !== []) {
            $html .= '<ul>' . implode('', array_map(fn($l) => '<li>' . htmlspecialchars($l, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</li>', $lines)) . '</ul>';
        }
        $email = (new Email())
            ->from('no-reply@neoodriver.test')
            ->to((string) $user->getEmail())
            ->subject('Mise à jour des documents')
            ->text($text)
            ->html($html);
        $this->mailer->send($email);
        return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
    }

    #[Route('/admin/users/{id}/company-docs/start', name: 'admin_user_companydocs_start', methods: ['POST'], requirements: ['id' => '\\d+'])]
    public function companyDocsStart(Request $request, int $id): Response
    {
        $user = $this->em->getRepository(User::class)->find($id);
        if (!$user instanceof User) {
            return $this->redirectToRoute('admin_users_list');
        }
        $token = (string) $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid('start_company_docs_' . $user->getId(), $token)) {
            return $this->redirectToRoute('admin_user_show', ['id' => $user->getId()]);
        }
        $session = $request->getSession();
        if ($session) {
            $session->set('wizard_user_id', $user->getId());
            $driver = $user->getDriverProfile();
            if ($driver instanceof Driver) {
                $session->set('wizard_driver_id', $driver->getId());
            }
        }
        return $this->redirectToRoute('admin_user_wizard', ['step' => 3]);
    }
}
