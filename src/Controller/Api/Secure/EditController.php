<?php

declare(strict_types=1);

namespace App\Controller\Api\Secure;

use App\Entity\User;
use App\Entity\Driver;
use App\Entity\DriverDocuments;
use App\Entity\Vehicle;
use App\Entity\Attachment;
use App\Enum\AttachmentField;
use App\Enum\AttachmentType;
use App\Enum\ValidationStatus;
use App\Service\Storage\R2Client;
use App\Form\UserWizard\UserStepType;
use App\Form\UserWizard\DriverDocumentsStepType;
use App\Form\Api\VehicleUpdateType;
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
            $view = $this->view(['error' => 'invalid'], Response::HTTP_BAD_REQUEST);
            return $this->handleView($view);
        }
        $this->em->flush();
        $context = new Context();
        $context->addGroup('me:read');
        $view = $this->view($user, Response::HTTP_OK);
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
            $view = $this->view(['error' => 'invalid'], Response::HTTP_BAD_REQUEST);
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
            $view = $this->view(['error' => 'invalid'], Response::HTTP_BAD_REQUEST);
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
