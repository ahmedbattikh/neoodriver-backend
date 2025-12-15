<?php
declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Driver;
use App\Entity\DriverDocuments;
use App\Entity\User;
use App\Entity\Vehicle;
use App\Enum\EnergyType;
use Doctrine\ORM\EntityManagerInterface;
use App\Enum\AttachmentField;
use App\Enum\AttachmentType;
use App\Entity\Attachment;
use App\Service\Storage\R2Client;
use App\Enum\ValidationStatus;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

#[Route('/api/secure', name: 'api_secure_', methods: ['POST'])]
final class SecureProfileController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em, private readonly R2Client $r2, private readonly MailerInterface $mailer) {}

    private function data(Request $request): array
    {
        $contentType = (string) $request->headers->get('content-type', '');
        if ($contentType !== '' && str_contains(strtolower($contentType), 'application/json')) {
            try { return $request->toArray(); } catch (\Throwable) { return []; }
        }
        return $request->request->all();
    }

    #[Route('/driver', name: 'driver')]
    public function addDriver(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user instanceof User) {
            return $this->json(['error' => 'unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $data = $this->data($request);

        $driver = $user->getDriverProfile();
        if (!$driver instanceof Driver) {
            $driver = new Driver();
            $driver->setUser($user);
            $this->em->persist($driver);
        }

        $this->em->flush(); // ensure driver has an ID for attachment keys

        if (array_key_exists('active', $data)) {
            $driver->setActive(filter_var($data['active'], FILTER_VALIDATE_BOOL));
        }

        $docs = $driver->getDocuments();
        if (!$docs instanceof DriverDocuments) {
            $docs = new DriverDocuments();
            $docs->setDriver($driver);
            $this->em->persist($docs);
        }
        $docs->setVtcCardValid(ValidationStatus::VALIDATION_INPROGRESS);
        if (!empty($data['vtcCardExpirationDate'])) {
            try { $docs->setVtcCardExpirationDate(new \DateTimeImmutable((string) $data['vtcCardExpirationDate'])); } catch (\Throwable) {}
        }
        $docs->setDrivingLicenseValid(ValidationStatus::VALIDATION_INPROGRESS);
        if (!empty($data['drivingLicenseExpirationDate'])) {
            try { $docs->setDrivingLicenseExpirationDate(new \DateTimeImmutable((string) $data['drivingLicenseExpirationDate'])); } catch (\Throwable) {}
        }
        $docs->setIdentityCardValid(ValidationStatus::VALIDATION_INPROGRESS);
        if (!empty($data['identityCardExpirationDate'])) {
            try { $docs->setIdentityCardExpirationDate(new \DateTimeImmutable((string) $data['identityCardExpirationDate'])); } catch (\Throwable) {}
        }
        $docs->setHealthCardValid(ValidationStatus::VALIDATION_INPROGRESS);
        if (array_key_exists('socialSecurityNumber', $data)) { $docs->setSocialSecurityNumber($data['socialSecurityNumber'] !== '' ? (string) $data['socialSecurityNumber'] : null); }
        $docs->setBankStatementValid(ValidationStatus::VALIDATION_INPROGRESS);
        if (array_key_exists('iban', $data)) { $docs->setIban($data['iban'] !== '' ? (string) $data['iban'] : null); }
        if (array_key_exists('isHosted', $data)) { $docs->setIsHosted(filter_var($data['isHosted'], FILTER_VALIDATE_BOOL)); }
        $docs->setProofOfResidenceValid(ValidationStatus::VALIDATION_INPROGRESS);
        $docs->setSecureDrivingRightCertificateValid(ValidationStatus::VALIDATION_INPROGRESS);

        // Optional file attachments (ignored for validation, flags remain false)
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
        foreach ($fileMap as $field => $af) {
            $uploaded = $request->files->get($field);
            if ($uploaded instanceof UploadedFile) {
                $att = $this->uploadAttachment($uploaded, $af, $user, (int) $driver->getId());
                $setter = 'set' . ucfirst($field);
                $docs->$setter($att);
            }
        }

        $user->setDriverStepCompleted(true);

        $this->em->flush();

        return $this->json([
            'status' => 'ok',
            'driverId' => $driver->getId(),
            'driverStepCompleted' => $user->isDriverStepCompleted(),
        ], Response::HTTP_CREATED);
    }

    #[Route('/vehicle', name: 'vehicle')]
    public function addVehicle(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user instanceof User) {
            return $this->json(['error' => 'unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        $driver = $user->getDriverProfile();
        if (!$driver instanceof Driver) {
            $driver = new Driver();
            $driver->setUser($user);
            $this->em->persist($driver);
            $this->em->flush();
        }

        $data = $this->data($request);

        $vehicle = new Vehicle();
        $vehicle->setDriver($driver);
        if (isset($data['registrationNumber'])) {
            $vehicle->setRegistrationNumber((string) $data['registrationNumber']);
        }
        if (isset($data['make'])) {
            $vehicle->setMake((string) $data['make']);
        }
        if (isset($data['model'])) {
            $vehicle->setModel((string) $data['model']);
        }
        if (isset($data['firstRegistrationYear'])) {
            $vehicle->setFirstRegistrationYear((int) $data['firstRegistrationYear']);
        }
        if (!empty($data['registrationDate'])) {
            try { $vehicle->setRegistrationDate(new \DateTimeImmutable((string) $data['registrationDate'])); } catch (\Throwable) {}
        }
        if (isset($data['seatCount'])) {
            $vehicle->setSeatCount((int) $data['seatCount']);
        }
        if (!empty($data['energyType'])) {
            $et = strtoupper((string) $data['energyType']);
            $vehicle->setEnergyType(EnergyType::tryFrom($et) ?? EnergyType::OTHER);
        }
        if (!empty($data['insuranceExpirationDate'])) {
            try { $vehicle->setInsuranceExpirationDate(new \DateTimeImmutable((string) $data['insuranceExpirationDate'])); } catch (\Throwable) {}
        }

        $this->em->persist($vehicle);
        $this->em->flush(); // ensure vehicle has an ID for attachment keys

        // Optional file attachments
        $vFileMap = [
            'registrationCertificate' => AttachmentField::VEHICLE_REGISTRATION_CERTIFICATE,
            'paidTransportInsuranceCertificate' => AttachmentField::VEHICLE_PAID_TRANSPORT_INSURANCE_CERTIFICATE,
            'technicalInspection' => AttachmentField::VEHICLE_TECHNICAL_INSPECTION,
            'vehicleFrontPhoto' => AttachmentField::VEHICLE_VEHICLE_FRONT_PHOTO,
            'insuranceNote' => AttachmentField::VEHICLE_INSURANCE_NOTE,
        ];
        foreach ($vFileMap as $field => $af) {
            $uploaded = $request->files->get($field);
            if ($uploaded instanceof UploadedFile) {
                $att = $this->uploadAttachment($uploaded, $af, $user, (int) $vehicle->getId());
                $setter = 'set' . ucfirst($field);
                $vehicle->$setter($att);
            }
        }

        $user->setVehicleStepCompleted(true);
        $this->em->flush();

        $email = (new TemplatedEmail())
            ->from('no-reply@neoodriver.test')
            ->to((string) $user->getEmail())
            ->subject('Merci — validation de compte')
            ->htmlTemplate('email/vehicle_submitted.html.twig')
            ->textTemplate('email/vehicle_submitted.txt.twig')
            ->context(['user' => $user, 'vehicle' => $vehicle]);
        $this->mailer->send($email);

        return $this->json([
            'status' => 'ok',
            'vehicleId' => $vehicle->getId(),
            'vehicleStepCompleted' => $user->isVehicleStepCompleted(),
        ], Response::HTTP_CREATED);
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
