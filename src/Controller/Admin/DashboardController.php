<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
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

#[IsGranted('ROLE_SUPER_ADMIN')]
final class DashboardController extends AbstractDashboardController
{
    public function __construct(private readonly EntityManagerInterface $em, private readonly R2Client $r2, private readonly MailerInterface $mailer, private readonly BoltService $bolt, private readonly ?LoggerInterface $logger = null) {}
    #[Route('/admin', name: 'admin')]
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
        ]);
    }



    #[Route('/admin/users', name: 'admin_users_list')]
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

    #[Route('/admin/users/unverified', name: 'admin_users_unverified')]
    public function usersUnverified(): Response
    {
        $users = $this->em->getRepository(User::class)->findBy(['verified' => false], ['id' => 'DESC']);
        return $this->render('admin/users_list.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/admin/users/{id}', name: 'admin_user_show', methods: ['GET'], requirements: ['id' => '\\d+'])]
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
        $driver = $user->getDriverProfile();
        $operations = [];
        $batches = [];
        $integrationCodes = [];
        $opsPage = max(1, (int) $request->query->get('ops_page', 1));
        $opsSize = max(1, min(100, (int) $request->query->get('ops_size', 20)));
        $opsOffset = ($opsPage - 1) * $opsSize;
        $batchesPage = max(1, (int) $request->query->get('batches_page', 1));
        $batchesSize = max(1, min(100, (int) $request->query->get('batches_size', 20)));
        $batchesOffset = ($batchesPage - 1) * $batchesSize;
        $activeTab = (string) $request->query->get('tab', 'info');
        $weekIn = 0.0;
        $weekOut = 0.0;
        $weekNet = 0.0;
        $weekStart = new \DateTimeImmutable('monday this week 00:00:00');
        $weekEnd = new \DateTimeImmutable('monday next week 00:00:00');
        $integrationAccounts = [];
        $enabledIntegrations = [];
        if ($driver instanceof Driver) {
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
            $integrationAccounts = $driver->getIntegrationAccounts()->toArray();
            $enabledIntegrations = $this->em->getRepository(DriverIntegration::class)->findBy(['enabled' => true], ['name' => 'ASC']);
        }
        $validationChoices = [
            'VALIDATION_INPROGRESS',
            'DOCUMENT_INVALIDE',
            'DOCUMENT_VALID',
            'DOCUMENT_REJECTED',
        ];
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
        ]);
    }

    #[Route('/admin/users/{id}/edit', name: 'admin_user_edit', methods: ['GET', 'POST'], requirements: ['id' => '\\d+'])]
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
            $startTs = 1767291077;
            $endTs = 1767809477;
            $companyIds = $integration->getBoltCompanyIds();
            $ordersPayload = $this->bolt->getFleetOrders($accessToken, 0, 1000, $companyIds, $startTs, $endTs, 'price_review');


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
