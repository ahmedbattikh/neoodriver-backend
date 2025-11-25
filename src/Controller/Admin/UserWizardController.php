<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\CompanyDocuments;
use App\Entity\Driver;
use App\Entity\DriverDocuments;
use App\Entity\User;
use App\Enum\UserRole;
use App\Form\UserWizard\CompanyDocumentsStepType;
use App\Form\UserWizard\DriverDocumentsStepType;
use App\Form\UserWizard\DriverStepType;
use App\Form\UserWizard\UserStepType;
use App\Form\UserWizard\VehicleStepType;
use App\Entity\Vehicle;
use App\Service\Storage\R2Client;
use App\Enum\AttachmentField;
use App\Enum\AttachmentType;
use App\Entity\Attachment;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_SUPER_ADMIN')]
final class UserWizardController extends AbstractDashboardController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly RequestStack $requestStack,
        private readonly R2Client $r2,
    ) {}

    #[Route('/admin/users/create', name: 'admin_user_wizard', methods: ['GET', 'POST'])]
    public function __invoke(Request $request): Response
    {
        $step = (int)($request->query->get('step') ?? 1);
        if ($step < 1) { $step = 1; }
        if ($step > 5) { $step = 5; }

        if ($step === 1) {
            $user = new User();
            $form = $this->createForm(UserStepType::class, $user);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $plain = (string)$form->get('plainPassword')->getData();
                $user->setPassword($this->passwordHasher->hashPassword($user, $plain));
                $user->setRoles(['ROLE_DRIVER']);
                $user->setRole(UserRole::DRIVER);
                $this->em->persist($user);
                $this->em->flush();
                if ($user->getReference()) {
                    $this->r2->ensureUserFolders($user->getReference());
                }
                $driver = new Driver();
                $driver->setUser($user);
                $this->em->persist($driver);
                $this->em->flush();
                $this->requestStack->getSession()?->set('wizard_user_id', $user->getId());
                $this->requestStack->getSession()?->set('wizard_driver_id', $driver->getId());
                return $this->redirectToRoute('admin_user_wizard', ['step' => 2]);
            }
            return $this->render('admin/user_wizard.html.twig', [
                'step' => 1,
                'form' => $form->createView(),
                'title' => 'Step 1 — User Information',
            ]);
        }

        $userId = (int)($this->requestStack->getSession()?->get('wizard_user_id', 0) ?? 0);
        if ($userId <= 0) {
            return $this->redirectToRoute('admin_user_wizard', ['step' => 1]);
        }
        $user = $this->em->getRepository(User::class)->find($userId);
        if (!$user instanceof User) {
            return $this->redirectToRoute('admin_user_wizard', ['step' => 1]);
        }
        $driver = null;
        $driverId = (int)($this->requestStack->getSession()?->get('wizard_driver_id', 0) ?? 0);
        if ($driverId > 0) {
            $driver = $this->em->getRepository(Driver::class)->find($driverId);
        }
        if (!$driver instanceof Driver) {
            $driver = $user->getDriverProfile();
            if (!$driver instanceof Driver) {
                $driver = new Driver();
                $driver->setUser($user);
                $this->em->persist($driver);
                $this->em->flush();
            }
            $this->requestStack->getSession()?->set('wizard_driver_id', $driver->getId());
        }

        if ($step === 2) {
            $driverDocs = $driver->getDocuments() ?? new DriverDocuments();
            $driverDocs->setDriver($driver);
            $form = $this->createForm(DriverDocumentsStepType::class, $driverDocs, ['user' => $user]);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $map = [
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
                foreach ($map as $field => $af) {
                    $uploaded = $form->get($field)->getData();
                    if ($uploaded instanceof UploadedFile) {
                        $att = $this->uploadAttachment($uploaded, $af, $user, $driver->getId());
                        $setter = 'set' . ucfirst($field);
                        $driverDocs->$setter($att);
                    }
                }
                if ($driverDocs->getId() === null) {
                    $this->em->persist($driverDocs);
                }
                $driver->setDocuments($driverDocs);
                $this->em->flush();
                $this->requestStack->getSession()?->set('wizard_driver_docs_id', $driverDocs->getId());
                return $this->redirectToRoute('admin_user_wizard', ['step' => 3]);
            }
            return $this->render('admin/user_wizard.html.twig', [
                'step' => 2,
                'form' => $form->createView(),
                'title' => 'Step 2 — Driver Documents',
            ]);
        }

        


        $companyDocs = $driver->getCompanyDocuments() ?? new CompanyDocuments();
        $companyDocs->setDriver($driver);
        $form = $this->createForm(CompanyDocumentsStepType::class, $companyDocs);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $map = [
                'employmentContract' => AttachmentField::COMPANY_EMPLOYMENT_CONTRACT,
                'employerCertificate' => AttachmentField::COMPANY_EMPLOYER_CERTIFICATE,
                'preEmploymentDeclaration' => AttachmentField::COMPANY_PRE_EMPLOYMENT_DECLARATION,
                'mutualInsuranceCertificate' => AttachmentField::COMPANY_MUTUAL_INSURANCE_CERTIFICATE,
                'urssafComplianceCertificate' => AttachmentField::COMPANY_URSSAF_COMPLIANCE_CERTIFICATE,
                'kbisExtract' => AttachmentField::COMPANY_KBIS_EXTRACT,
                'revtcRegistrationCertificate' => AttachmentField::COMPANY_REVTC_REGISTRATION_CERTIFICATE,
            ];
            foreach ($map as $field => $af) {
                $uploaded = $form->get($field)->getData();
                if ($uploaded instanceof UploadedFile) {
                    $att = $this->uploadAttachment($uploaded, $af, $user, $driver->getId());
                    $setter = 'set' . ucfirst($field);
                    $companyDocs->$setter($att);
                }
            }
            if ($companyDocs->getId() === null) {
                $this->em->persist($companyDocs);
            }
            $driver->setCompanyDocuments($companyDocs);
            $this->em->flush();
            return $this->redirectToRoute('admin_user_wizard', ['step' => 4]);
        }
        if ($step === 3) {
            return $this->render('admin/user_wizard.html.twig', [
                'step' => 3,
                'form' => $form->createView(),
                'title' => 'Step 3 — Company Documents',
            ]);
        }

        if ($step === 4) {
            $vehicle = new Vehicle();
            $vehicle->setDriver($driver);
            $vehicleForm = $this->createForm(VehicleStepType::class, $vehicle);
            $vehicleForm->handleRequest($request);
            if ($vehicleForm->isSubmitted() && $vehicleForm->isValid()) {
                $this->em->persist($vehicle);
                $this->em->flush();
                $map = [
                    'registrationCertificate' => AttachmentField::VEHICLE_REGISTRATION_CERTIFICATE,
                    'paidTransportInsuranceCertificate' => AttachmentField::VEHICLE_PAID_TRANSPORT_INSURANCE_CERTIFICATE,
                    'technicalInspection' => AttachmentField::VEHICLE_TECHNICAL_INSPECTION,
                    'vehicleFrontPhoto' => AttachmentField::VEHICLE_VEHICLE_FRONT_PHOTO,
                    'insuranceNote' => AttachmentField::VEHICLE_INSURANCE_NOTE,
                ];
                foreach ($map as $field => $af) {
                    $uploaded = $vehicleForm->get($field)->getData();
                    if ($uploaded instanceof UploadedFile) {
                        $att = $this->uploadAttachment($uploaded, $af, $user, $vehicle->getId());
                        $setter = 'set' . ucfirst($field);
                        $vehicle->$setter($att);
                    }
                }
                $this->em->flush();
                return $this->redirectToRoute('admin_user_wizard', ['step' => 5]);
            }
            return $this->render('admin/user_wizard.html.twig', [
                'step' => 4,
                'form' => $vehicleForm->createView(),
                'title' => 'Step 4 — Vehicle',
            ]);
        }

        $driverForm = $this->createForm(DriverStepType::class, $driver);
        $driverForm->handleRequest($request);
        if ($driverForm->isSubmitted() && $driverForm->isValid()) {
            $user->setDriverProfile($driver);
            $this->em->flush();
            $this->requestStack->getSession()?->remove('wizard_user_id');
            $this->requestStack->getSession()?->remove('wizard_driver_id');
            $this->requestStack->getSession()?->remove('wizard_driver_docs_id');
            return $this->redirectToRoute('admin');
        }
        return $this->render('admin/user_wizard.html.twig', [
            'step' => 5,
            'form' => $driverForm->createView(),
            'title' => 'Step 5 — Driver Information',
        ]);
    }

    #[Route('/admin/users/{id}/resume/{step}', name: 'admin_user_wizard_resume', methods: ['GET'], requirements: ['id' => '\\d+', 'step' => '\\d+'])]
    public function resume(int $id, int $step): Response
    {
        if ($step < 1) { $step = 1; }
        if ($step > 5) { $step = 5; }
        $user = $this->em->getRepository(User::class)->find($id);
        if (!$user instanceof User) {
            return $this->redirectToRoute('admin_user_wizard', ['step' => 1]);
        }
        $this->requestStack->getSession()?->set('wizard_user_id', $user->getId());
        $driver = $user->getDriverProfile();
        if (!$driver instanceof Driver) {
            $driver = new Driver();
            $driver->setUser($user);
            $this->em->persist($driver);
            $this->em->flush();
        }
        $this->requestStack->getSession()?->set('wizard_driver_id', $driver->getId());
        return $this->redirectToRoute('admin_user_wizard', ['step' => $step]);
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