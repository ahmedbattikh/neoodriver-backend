<?php

declare(strict_types=1);

namespace App\Controller\Api\Secure;

use App\Entity\User;
use App\Entity\Driver;
use App\Entity\DriverDocuments;
use App\Entity\Vehicle;
use App\Entity\Attachment;
use App\Entity\ExpenseNote;
use App\Entity\PaymentOperation;
use App\Entity\PaymentBatch;
use App\Entity\DriverIntegration;
use App\Enum\AttachmentField;
use App\Enum\AttachmentType;
use App\Enum\ValidationStatus;
use App\Service\Storage\R2Client;
use App\Form\UserWizard\UserStepType;
use App\Form\UserWizard\DriverDocumentsStepType;
use App\Form\Api\VehicleUpdateType;
use App\Form\Api\ExpenseNoteCreateType;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Context\Context;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/secure', name: 'api_secure_')]
final class EditController extends AbstractFOSRestController
{
    public function __construct(private readonly EntityManagerInterface $em, private readonly R2Client $r2) {}

    private function data(Request $request): array
    {
        $contentType = (string) $request->headers->get('content-type', '');
        if ($contentType !== '' && str_contains(strtolower($contentType), 'application/json')) {
            try {
                return $request->toArray();
            } catch (\Throwable) {
                return [];
            }
        }
        return $request->request->all();
    }

    #[Route('/payments-summary', name: 'payments_summary', methods: ['GET'])]
    public function paymentsSummary(Request $request, #[CurrentUser] ?User $user): Response
    {
        if (!$user instanceof User) {
            $view = $this->view(['error' => 'unauthorized'], Response::HTTP_UNAUTHORIZED);
            return $this->handleView($view);
        }
        $driver = $user->getDriverProfile();
        if (!$driver instanceof Driver) {
            $view = $this->view(['integrationsToday' => [], 'days' => []], Response::HTTP_OK);
            return $this->handleView($view);
        }
        $todayStart = new \DateTimeImmutable('today 00:00:00');
        $todayEnd = new \DateTimeImmutable('tomorrow 00:00:00');
        $rows = $this->em->createQueryBuilder()
            ->select("o.integrationCode AS code")
            ->addSelect("SUM(CASE WHEN LOWER(o.direction) IN ('in','credit') THEN o.amount ELSE 0 END) AS totalIn")
            ->addSelect("SUM(CASE WHEN LOWER(o.direction) IN ('out','debit') THEN o.amount ELSE 0 END) AS totalOut")
            ->from(PaymentOperation::class, 'o')
            ->where('o.driver = :driver')
            ->andWhere('o.occurredAt >= :start')
            ->andWhere('o.occurredAt < :end')
            ->groupBy('o.integrationCode')
            ->setParameter('driver', $driver)
            ->setParameter('start', $todayStart)
            ->setParameter('end', $todayEnd)
            ->getQuery()
            ->getArrayResult();
        $integrationsToday = [];
        foreach ($rows as $r) {
            $in = (float) (($r['totalIn'] ?? 0) ?: 0);
            $out = (float) (($r['totalOut'] ?? 0) ?: 0);
            $integrationsToday[] = [
                'integrationCode' => (string) $r['code'],
                'totalIn' => number_format($in, 3, '.', ''),
                'net' => number_format($in - $out, 3, '.', ''),
            ];
        }
        $beginRaw = (string) $request->query->get('dateBegin', '');
        $endRaw = (string) $request->query->get('dateEnd', '');
        if ($beginRaw === '' || $endRaw === '') {
            $begin = new \DateTimeImmutable('monday this week 00:00:00');
            $end = new \DateTimeImmutable('monday next week 00:00:00');
        } else {
            try {
                $begin = (new \DateTimeImmutable($beginRaw))->setTime(0, 0, 0);
                $end = (new \DateTimeImmutable($endRaw))->setTime(0, 0, 0)->modify('+1 day');
            } catch (\Throwable) {
                $begin = new \DateTimeImmutable('monday this week 00:00:00');
                $end = new \DateTimeImmutable('monday next week 00:00:00');
            }
        }
        $ops = $this->em->createQueryBuilder()
            ->select('o')
            ->from(PaymentOperation::class, 'o')
            ->where('o.driver = :driver')
            ->andWhere('o.occurredAt >= :begin')
            ->andWhere('o.occurredAt < :end')
            ->orderBy('o.occurredAt', 'ASC')
            ->setParameter('driver', $driver)
            ->setParameter('begin', $begin)
            ->setParameter('end', $end)
            ->getQuery()
            ->getResult();
        $daysMap = [];
        $cursor = $begin;
        while ($cursor < $end) {
            $key = $cursor->format('Y-m-d');
            $daysMap[$key] = ['totalIn' => 0.0, 'totalOut' => 0.0];
            $cursor = $cursor->modify('+1 day');
        }
        foreach ($ops as $o) {
            if ($o instanceof PaymentOperation) {
                $key = $o->getOccurredAt()->format('Y-m-d');
                if (!isset($daysMap[$key])) {
                    $daysMap[$key] = ['totalIn' => 0.0, 'totalOut' => 0.0];
                }
                $amt = (float) $o->getAmount();
                $dir = strtolower($o->getDirection());
                if ($dir === 'in' || $dir === 'credit') {
                    $daysMap[$key]['totalIn'] += $amt;
                } elseif ($dir === 'out' || $dir === 'debit') {
                    $daysMap[$key]['totalOut'] += $amt;
                }
            }
        }
        $days = [];
        foreach ($daysMap as $d => $vals) {
            $days[] = [
                'date' => $d,
                'totalIn' => number_format((float) $vals['totalIn'], 3, '.', ''),
                'net' => number_format((float) ($vals['totalIn'] - $vals['totalOut']), 3, '.', ''),
            ];
        }
        usort($days, function ($a, $b) {
            return strcmp($a['date'], $b['date']);
        });
        $view = $this->view(['integrationsToday' => $integrationsToday, 'days' => $days], Response::HTTP_OK);
        return $this->handleView($view);
    }

    private function filterNulls(array $data): array
    {
        $out = [];
        foreach ($data as $k => $v) {
            if ($v !== null) {
                $out[$k] = $v;
            }
        }
        return $out;
    }

    #[Route('/user', name: 'user_update', methods: ['PATCH'])]
    public function updateUser(Request $request, #[CurrentUser] ?User $user): Response
    {
        if (!$user instanceof User) {
            $view = $this->view(['error' => 'unauthorized'], Response::HTTP_UNAUTHORIZED);
            return $this->handleView($view);
        }
        $form = $this->createForm(UserStepType::class, $user);
        $form->submit($this->filterNulls($this->data($request)), false);
        if (!$form->isSubmitted() || !$form->isValid()) {
            $errs = [];
            foreach ($form->getErrors(true) as $e) {
                $origin = $e->getOrigin();
                $path = $origin ? $origin->getName() : '';
                $errs[] = ['field' => $path, 'message' => $e->getMessage()];
            }
            $view = $this->view(['error' => 'invalid', 'details' => $errs], Response::HTTP_BAD_REQUEST);
            return $this->handleView($view);
        }
        $this->em->flush();
        $context = new Context();
        $context->addGroup('me:read');
        $view = $this->view($user, Response::HTTP_OK);
        $view->setContext($context);
        return $this->handleView($view);
    }

    #[Route('/payment-operations', name: 'payment_operations_list', methods: ['GET'])]
    public function listPaymentOperations(Request $request, #[CurrentUser] ?User $user): Response
    {
        if (!$user instanceof User) {
            $view = $this->view(['error' => 'unauthorized'], Response::HTTP_UNAUTHORIZED);
            return $this->handleView($view);
        }
        $driver = $user->getDriverProfile();
        if (!$driver instanceof Driver) {
            $view = $this->view(['items' => [], 'page' => 1, 'size' => 0, 'total' => 0, 'totalPages' => 1], Response::HTTP_OK);
            return $this->handleView($view);
        }
        $page = max(1, (int) $request->query->get('page', 1));
        $size = max(1, min(100, (int) $request->query->get('size', 20)));
        $offset = ($page - 1) * $size;
        $sortBy = (string) $request->query->get('sort', 'occurredAt');
        $sortDir = strtoupper((string) $request->query->get('dir', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
        $allowedSort = ['occurredAt', 'amount', 'status', 'integrationCode', 'operationType', 'currency', 'direction'];
        if (!in_array($sortBy, $allowedSort, true)) {
            $sortBy = 'occurredAt';
        }
        $qb = $this->em->createQueryBuilder()
            ->select('o')
            ->from(PaymentOperation::class, 'o')
            ->where('o.driver = :driver')
            ->setParameter('driver', $driver);
        $intCode = (string) $request->query->get('integrationCode', '');
        if ($intCode !== '') {
            $qb->andWhere('o.integrationCode = :integrationCode')->setParameter('integrationCode', $intCode);
        }
        $opType = (string) $request->query->get('operationType', '');
        if ($opType !== '') {
            $qb->andWhere('o.operationType = :operationType')->setParameter('operationType', $opType);
        }
        $direction = strtolower((string) $request->query->get('direction', ''));
        if ($direction !== '') {
            $qb->andWhere('LOWER(o.direction) = :direction')->setParameter('direction', $direction);
        }
        $status = (string) $request->query->get('status', '');
        if ($status !== '') {
            $qb->andWhere('o.status = :status')->setParameter('status', $status);
        }
        $currency = (string) $request->query->get('currency', '');
        if ($currency !== '') {
            $qb->andWhere('o.currency = :currency')->setParameter('currency', $currency);
        }
        $from = (string) $request->query->get('dateFrom', '');
        if ($from !== '') {
            try {
                $df = new \DateTimeImmutable($from);
                $qb->andWhere('o.occurredAt >= :dateFrom')->setParameter('dateFrom', $df);
            } catch (\Throwable) {
            }
        }
        $to = (string) $request->query->get('dateTo', '');
        if ($to !== '') {
            try {
                $dt = new \DateTimeImmutable($to);
                $qb->andWhere('o.occurredAt <= :dateTo')->setParameter('dateTo', $dt);
            } catch (\Throwable) {
            }
        }
        $qb->orderBy('o.' . $sortBy, $sortDir)->setFirstResult($offset)->setMaxResults($size);
        $items = $qb->getQuery()->getResult();
        $rows = [];
        foreach ($items as $o) {
            if ($o instanceof PaymentOperation) {
                $rows[] = [
                    'id' => $o->getId(),
                    'integrationCode' => $o->getIntegrationCode(),
                    'operationType' => $o->getOperationType(),
                    'direction' => $o->getDirection(),
                    'amount' => $o->getAmount(),
                    'currency' => $o->getCurrency(),
                    'status' => $o->getStatus(),
                    'externalReference' => $o->getExternalReference(),
                    'description' => $o->getDescription(),
                    'occurredAt' => $o->getOccurredAt()->format('c'),
                    'createdAt' => $o->getCreatedAt()->format('c'),
                ];
            }
        }
        $countQb = $this->em->createQueryBuilder()
            ->select('COUNT(o.id)')
            ->from(PaymentOperation::class, 'o')
            ->where('o.driver = :driver')
            ->setParameter('driver', $driver);
        if ($intCode !== '') $countQb->andWhere('o.integrationCode = :integrationCode')->setParameter('integrationCode', $intCode);
        if ($opType !== '') $countQb->andWhere('o.operationType = :operationType')->setParameter('operationType', $opType);
        if ($direction !== '') $countQb->andWhere('LOWER(o.direction) = :direction')->setParameter('direction', $direction);
        if ($status !== '') $countQb->andWhere('o.status = :status')->setParameter('status', $status);
        if ($currency !== '') $countQb->andWhere('o.currency = :currency')->setParameter('currency', $currency);
        if ($from !== '') {
            try {
                $df = new \DateTimeImmutable($from);
                $countQb->andWhere('o.occurredAt >= :dateFrom')->setParameter('dateFrom', $df);
            } catch (\Throwable) {
            }
        }
        if ($to !== '') {
            try {
                $dt = new \DateTimeImmutable($to);
                $countQb->andWhere('o.occurredAt <= :dateTo')->setParameter('dateTo', $dt);
            } catch (\Throwable) {
            }
        }
        $total = (int) $countQb->getQuery()->getSingleScalarResult();
        $totalPages = max(1, (int) ceil($total / $size));
        $view = $this->view(['items' => $rows, 'page' => $page, 'size' => $size, 'total' => $total, 'totalPages' => $totalPages], Response::HTTP_OK);
        return $this->handleView($view);
    }

    #[Route('/payment-batches', name: 'payment_batches_list', methods: ['GET'])]
    public function listPaymentBatches(Request $request, #[CurrentUser] ?User $user): Response
    {
        if (!$user instanceof User) {
            $view = $this->view(['error' => 'unauthorized'], Response::HTTP_UNAUTHORIZED);
            return $this->handleView($view);
        }
        $driver = $user->getDriverProfile();
        if (!$driver instanceof Driver) {
            $view = $this->view(['items' => [], 'page' => 1, 'size' => 0, 'total' => 0, 'totalPages' => 1], Response::HTTP_OK);
            return $this->handleView($view);
        }
        $codesRows = $this->em->createQueryBuilder()
            ->select('DISTINCT o.integrationCode')
            ->from(PaymentOperation::class, 'o')
            ->where('o.driver = :driver')
            ->setParameter('driver', $driver)
            ->getQuery()
            ->getArrayResult();
        $allowedCodes = array_values(array_unique(array_map(fn($r) => (string) ($r['integrationCode'] ?? ''), $codesRows)));
        $intCode = (string) $request->query->get('integrationCode', '');
        if ($intCode !== '' && !in_array($intCode, $allowedCodes, true)) {
            $view = $this->view(['items' => [], 'page' => 1, 'size' => 0, 'total' => 0, 'totalPages' => 1], Response::HTTP_OK);
            return $this->handleView($view);
        }
        $page = max(1, (int) $request->query->get('page', 1));
        $size = max(1, min(100, (int) $request->query->get('size', 20)));
        $offset = ($page - 1) * $size;
        $sortBy = (string) $request->query->get('sort', 'periodStart');
        $sortDir = strtoupper((string) $request->query->get('dir', 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
        $allowedSort = ['periodStart', 'periodEnd', 'totalAmount'];
        if (!in_array($sortBy, $allowedSort, true)) {
            $sortBy = 'periodStart';
        }
        $qb = $this->em->createQueryBuilder()
            ->select('b')
            ->from(PaymentBatch::class, 'b');
        if ($intCode !== '') {
            $integration = $this->em->getRepository(DriverIntegration::class)->findOneBy(['code' => $intCode]);
            if ($integration instanceof DriverIntegration) {
                $qb->andWhere('b.integration = :integration')->setParameter('integration', $integration);
            } else {
                $qb->andWhere('1 = 0');
            }
        } else {
            if ($allowedCodes !== []) {
                $integrations = [];
                foreach ($allowedCodes as $code) {
                    $i = $this->em->getRepository(DriverIntegration::class)->findOneBy(['code' => $code]);
                    if ($i instanceof DriverIntegration) {
                        $integrations[] = $i;
                    }
                }
                if ($integrations !== []) {
                    $qb->andWhere('b.integration IN (:integrations)')->setParameter('integrations', $integrations);
                } else {
                    $qb->andWhere('1 = 0');
                }
            } else {
                $qb->andWhere('1 = 0');
            }
        }
        $from = (string) $request->query->get('periodFrom', '');
        if ($from !== '') {
            try {
                $df = new \DateTimeImmutable($from);
                $qb->andWhere('b.periodStart >= :periodFrom')->setParameter('periodFrom', $df);
            } catch (\Throwable) {
            }
        }
        $to = (string) $request->query->get('periodTo', '');
        if ($to !== '') {
            try {
                $dt = new \DateTimeImmutable($to);
                $qb->andWhere('b.periodEnd <= :periodTo')->setParameter('periodTo', $dt);
            } catch (\Throwable) {
            }
        }
        $qb->orderBy('b.' . $sortBy, $sortDir)->setFirstResult($offset)->setMaxResults($size);
        $items = $qb->getQuery()->getResult();
        $rows = [];
        foreach ($items as $b) {
            if ($b instanceof PaymentBatch) {
                $rows[] = [
                    'id' => $b->getId(),
                    'integrationCode' => $b->getIntegration()->getCode(),
                    'periodStart' => $b->getPeriodStart()->format('Y-m-d'),
                    'periodEnd' => $b->getPeriodEnd()->format('Y-m-d'),
                    'totalAmount' => $b->getTotalAmount(),
                ];
            }
        }
        $countQb = $this->em->createQueryBuilder()
            ->select('COUNT(b.id)')
            ->from(PaymentBatch::class, 'b');
        if ($intCode !== '') {
            $integration = $this->em->getRepository(DriverIntegration::class)->findOneBy(['code' => $intCode]);
            if ($integration instanceof DriverIntegration) {
                $countQb->andWhere('b.integration = :integration')->setParameter('integration', $integration);
            } else {
                $countQb->andWhere('1 = 0');
            }
        } else {
            if ($allowedCodes !== []) {
                $integrations = [];
                foreach ($allowedCodes as $code) {
                    $i = $this->em->getRepository(DriverIntegration::class)->findOneBy(['code' => $code]);
                    if ($i instanceof DriverIntegration) {
                        $integrations[] = $i;
                    }
                }
                if ($integrations !== []) {
                    $countQb->andWhere('b.integration IN (:integrations)')->setParameter('integrations', $integrations);
                } else {
                    $countQb->andWhere('1 = 0');
                }
            } else {
                $countQb->andWhere('1 = 0');
            }
        }
        if ($from !== '') {
            try {
                $df = new \DateTimeImmutable($from);
                $countQb->andWhere('b.periodStart >= :periodFrom')->setParameter('periodFrom', $df);
            } catch (\Throwable) {
            }
        }
        if ($to !== '') {
            try {
                $dt = new \DateTimeImmutable($to);
                $countQb->andWhere('b.periodEnd <= :periodTo')->setParameter('periodTo', $dt);
            } catch (\Throwable) {
            }
        }
        $total = (int) $countQb->getQuery()->getSingleScalarResult();
        $totalPages = max(1, (int) ceil($total / $size));
        $view = $this->view(['items' => $rows, 'page' => $page, 'size' => $size, 'total' => $total, 'totalPages' => $totalPages], Response::HTTP_OK);
        return $this->handleView($view);
    }

    #[Route('/expense-notes', name: 'expense_note_create', methods: ['POST'])]
    public function createExpenseNote(Request $request, #[CurrentUser] ?User $user): Response
    {
        if (!$user instanceof User) {
            $view = $this->view(['error' => 'unauthorized'], Response::HTTP_UNAUTHORIZED);
            return $this->handleView($view);
        }
        $driver = $user->getDriverProfile();
        if (!$driver instanceof Driver) {
            $driver = new Driver();
            $driver->setUser($user);
            $this->em->persist($driver);
            $this->em->flush();
        }
        $note = new ExpenseNote();
        $note->setDriver($driver);
        $form = $this->createForm(ExpenseNoteCreateType::class, $note);
        $form->submit($this->filterNulls($this->data($request)), false);
        if (!$form->isSubmitted() || !$form->isValid()) {
            $errs = [];
            foreach ($form->getErrors(true) as $e) {
                $origin = $e->getOrigin();
                $path = $origin ? $origin->getName() : '';
                $errs[] = ['field' => $path, 'message' => $e->getMessage()];
            }
            $view = $this->view(['error' => 'invalid', 'details' => $errs], Response::HTTP_BAD_REQUEST);
            return $this->handleView($view);
        }
        $date = $form->get('noteDate')->getData();
        if ($date instanceof \DateTimeImmutable) {
            $note->setNoteDate($date);
        } else {
            $rawDate = (string) $request->request->get('noteDate', '');
            try {
                $note->setNoteDate(new \DateTimeImmutable($rawDate));
            } catch (\Throwable) {
                $view = $this->view(['error' => 'invalid', 'details' => [['field' => 'noteDate', 'message' => 'Invalid date']]], Response::HTTP_BAD_REQUEST);
                return $this->handleView($view);
            }
        }
        $this->em->persist($note);
        $this->em->flush();
        $uploaded = $request->files->get('invoice');
        if (!$uploaded instanceof UploadedFile) {
            $view = $this->view(['error' => 'invoice_required'], Response::HTTP_BAD_REQUEST);
            return $this->handleView($view);
        }
        $att = $this->uploadAttachment($uploaded, AttachmentField::EXPENSE_INVOICE, $user, (int) $note->getId());
        $note->setInvoice($att);
        $this->em->flush();
        $context = new Context();
        $context->addGroup('me:read');
        $view = $this->view($note, Response::HTTP_CREATED);
        $view->setContext($context);
        return $this->handleView($view);
    }

    #[Route('/driver-documents', name: 'driver_documents_update', methods: ['PATCH'])]
    public function updateDriverDocuments(Request $request, #[CurrentUser] ?User $user): Response
    {
        if (!$user instanceof User) {
            $view = $this->view(['error' => 'unauthorized'], Response::HTTP_UNAUTHORIZED);
            return $this->handleView($view);
        }
        $driver = $user->getDriverProfile();
        if (!$driver instanceof Driver) {
            $driver = new Driver();
            $driver->setUser($user);
            $this->em->persist($driver);
            $this->em->flush();
        }
        $docs = $driver->getDocuments() ?? new DriverDocuments();
        $docs->setDriver($driver);
        $data = $this->filterNulls($this->data($request));
        foreach (['vtcCardValid', 'drivingLicenseValid', 'identityCardValid', 'healthCardValid', 'bankStatementValid', 'proofOfResidenceValid', 'secureDrivingRightCertificateValid'] as $blocked) {
            if (array_key_exists($blocked, $data)) {
                unset($data[$blocked]);
            }
        }
        $form = $this->createForm(DriverDocumentsStepType::class, $docs);
        $form->submit($data, false);
        if (!$form->isSubmitted() || !$form->isValid()) {
            $errs = [];
            foreach ($form->getErrors(true) as $e) {
                $origin = $e->getOrigin();
                $path = $origin ? $origin->getName() : '';
                $errs[] = ['field' => $path, 'message' => $e->getMessage()];
            }
            $view = $this->view(['error' => 'invalid', 'details' => $errs], Response::HTTP_BAD_REQUEST);
            return $this->handleView($view);
        }
        $this->em->flush(); // ensure driver/docs have IDs

        $fileMap = [
            'identityPhoto' => AttachmentField::DRIVER_IDENTITY_PHOTO,
            'vtcCardFront' => AttachmentField::DRIVER_VTC_CARD_FRONT,
            'vtcCardBack' => AttachmentField::DRIVER_VTC_CARD_BACK,
            'drivingLicenseFront' => AttachmentField::DRIVER_DRIVING_LICENSE_FRONT,
            'drivingLicenseBack' => AttachmentField::DRIVER_DRIVING_LICENSE_BACK,
            'identityCardFront' => AttachmentField::DRIVER_IDENTITY_CARD_FRONT,
            'identityCardBack' => AttachmentField::DRIVER_IDENTITY_CARD_BACK,
            'healthCard' => AttachmentField::DRIVER_HEALTH_CARD,
            'bankStatement' => AttachmentField::DRIVER_BANK_STATEMENT,
            'proofOfResidence' => AttachmentField::DRIVER_PROOF_OF_RESIDENCE,
            'secureDrivingRightCertificate' => AttachmentField::DRIVER_SECURE_DRIVING_RIGHT_CERTIFICATE,
        ];
        $updatedAny = false;
        foreach ($fileMap as $field => $af) {
            $uploaded = $request->files->get($field);
            if ($uploaded instanceof UploadedFile) {
                $att = $this->uploadAttachment($uploaded, $af, $user, (int) $driver->getId());
                $setter = 'set' . ucfirst($field);
                $docs->$setter($att);
                $updatedAny = true;
                if (in_array($field, ['vtcCardFront', 'vtcCardBack'], true)) {
                    $docs->setVtcCardValid(ValidationStatus::VALIDATION_INPROGRESS);
                } elseif (in_array($field, ['drivingLicenseFront', 'drivingLicenseBack'], true)) {
                    $docs->setDrivingLicenseValid(ValidationStatus::VALIDATION_INPROGRESS);
                } elseif (in_array($field, ['identityCardFront', 'identityCardBack'], true)) {
                    $docs->setIdentityCardValid(ValidationStatus::VALIDATION_INPROGRESS);
                } elseif ($field === 'healthCard') {
                    $docs->setHealthCardValid(ValidationStatus::VALIDATION_INPROGRESS);
                } elseif ($field === 'bankStatement') {
                    $docs->setBankStatementValid(ValidationStatus::VALIDATION_INPROGRESS);
                } elseif ($field === 'proofOfResidence') {
                    $docs->setProofOfResidenceValid(ValidationStatus::VALIDATION_INPROGRESS);
                } elseif ($field === 'secureDrivingRightCertificate') {
                    $docs->setSecureDrivingRightCertificateValid(ValidationStatus::VALIDATION_INPROGRESS);
                }
            }
        }
        if ($docs->getId() === null) {
            $this->em->persist($docs);
        }
        $driver->setDocuments($docs);
        $this->em->flush();
        $context = new Context();
        $context->addGroup('me:read');
        $view = $this->view($docs, Response::HTTP_OK);
        $view->setContext($context);
        return $this->handleView($view);
    }

    #[Route('/vehicle/{id}', name: 'vehicle_update', requirements: ['id' => '\\d+'], methods: ['PATCH'])]
    public function updateVehicle(Request $request, #[CurrentUser] ?User $user, int $id): Response
    {
        if (!$user instanceof User) {
            $view = $this->view(['error' => 'unauthorized'], Response::HTTP_UNAUTHORIZED);
            return $this->handleView($view);
        }
        $vehicle = $this->em->getRepository(Vehicle::class)->find($id);
        if (!$vehicle instanceof Vehicle) {
            $view = $this->view(['error' => 'not_found'], Response::HTTP_NOT_FOUND);
            return $this->handleView($view);
        }
        $driver = $user->getDriverProfile();
        if (!$driver instanceof Driver || $vehicle->getDriver() !== $driver) {
            $view = $this->view(['error' => 'forbidden'], Response::HTTP_FORBIDDEN);
            return $this->handleView($view);
        }
        $form = $this->createForm(VehicleUpdateType::class, $vehicle);
        $form->submit($this->filterNulls($this->data($request)), false);
        if (!$form->isSubmitted() || !$form->isValid()) {
            $errs = [];
            foreach ($form->getErrors(true) as $e) {
                $origin = $e->getOrigin();
                $path = $origin ? $origin->getName() : '';
                $errs[] = ['field' => $path, 'message' => $e->getMessage()];
            }
            $view = $this->view(['error' => 'invalid', 'details' => $errs], Response::HTTP_BAD_REQUEST);
            return $this->handleView($view);
        }
        $fileMap = [
            'registrationCertificate' => AttachmentField::VEHICLE_REGISTRATION_CERTIFICATE,
            'paidTransportInsuranceCertificate' => AttachmentField::VEHICLE_PAID_TRANSPORT_INSURANCE_CERTIFICATE,
            'technicalInspection' => AttachmentField::VEHICLE_TECHNICAL_INSPECTION,
            'vehicleFrontPhoto' => AttachmentField::VEHICLE_VEHICLE_FRONT_PHOTO,
            'insuranceNote' => AttachmentField::VEHICLE_INSURANCE_NOTE,
        ];
        foreach ($fileMap as $field => $af) {
            $uploaded = $request->files->get($field);
            if ($uploaded instanceof UploadedFile) {
                $att = $this->uploadAttachment($uploaded, $af, $user, (int) $vehicle->getId());
                $setter = 'set' . ucfirst($field);
                $vehicle->$setter($att);
            }
        }
        $this->em->flush();
        $context = new Context();
        $context->addGroup('me:read');
        $view = $this->view($vehicle, Response::HTTP_OK);
        $view->setContext($context);
        return $this->handleView($view);
    }

    private function uploadAttachment(UploadedFile $file, AttachmentField $af, User $user, int $recordId): Attachment
    {
        $ref = (string) $user->getReference();
        $baseKey = $af->key($ref, $recordId);
        $orig = (string) $file->getClientOriginalName();
        $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION) ?: 'bin');
        $mime = (string) ($file->getMimeType() ?: 'application/octet-stream');
        $key = $baseKey . '.' . $ext;
        $body = file_get_contents($file->getPathname());
        $this->r2->putObject($key, $body !== false ? $body : '', $mime);
        $att = new Attachment();
        $att->setFileName(basename($key));
        $att->setOriginalFileName($orig !== '' ? $orig : null);
        $att->setFilePath($ref . '/' . $af->folder() . '/' . basename($key));
        $att->setFileSize($file->getSize() ?? strlen($body ?: ''));
        $att->setMimeType($mime);
        $att->setType($this->attachmentTypeFromMime($mime));
        $att->setIsPrivate(true);
        $att->setIsActive(true);
        $att->setChecksum(hash_file('sha256', $file->getPathname()));
        $att->setUser($user);
        $this->em->persist($att);
        return $att;
    }

    private function attachmentTypeFromMime(string $mime): AttachmentType
    {
        $m = strtolower($mime);
        if (str_starts_with($m, 'image/')) return AttachmentType::IMAGE;
        if ($m === 'application/pdf') return AttachmentType::PDF;
        if (str_starts_with($m, 'video/')) return AttachmentType::VIDEO;
        if (str_starts_with($m, 'audio/')) return AttachmentType::AUDIO;
        if (in_array($m, ['application/zip', 'application/x-7z-compressed', 'application/x-tar', 'application/x-gzip'], true)) return AttachmentType::ARCHIVE;
        if (in_array($m, ['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'], true)) return AttachmentType::SPREADSHEET;
        if (in_array($m, ['application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation'], true)) return AttachmentType::PRESENTATION;
        if (in_array($m, ['text/plain', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'], true)) return AttachmentType::DOCUMENT;
        return AttachmentType::OTHER;
    }
}
