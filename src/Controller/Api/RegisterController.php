<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Attachment;
use App\Entity\User;
use App\Enum\AttachmentField;
use App\Enum\AttachmentType;
use App\Repository\UserRepository;
use App\Service\OtpLoginService;
use App\Service\Storage\R2Client;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Email as EmailConstraint;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

#[Route('/api/register', name: 'api_register', methods: ['POST'])]
final class RegisterController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $users,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly ValidatorInterface $validator,
        private readonly OtpLoginService $otpService,
        private readonly R2Client $r2,
        private readonly MailerInterface $mailer,
    ) {}

    private function getField(Request $request, string $key): string
    {
        $value = '';
        $contentType = (string) $request->headers->get('content-type', '');
        if ($contentType !== '' && str_contains(strtolower($contentType), 'application/json')) {
            try {
                $data = $request->toArray();
                $value = (string) ($data[$key] ?? '');
            } catch (\Throwable) {
            }
        }
        if ($value === '') {
            $value = (string) $request->request->get($key, '');
        }
        if ($value === '') {
            $value = (string) $request->query->get($key, '');
        }
        return $value;
    }

    public function __invoke(Request $request): JsonResponse
    {
        $email = $this->getField($request, 'email');
        $violations = $this->validator->validate($email, [
            new NotBlank(message: 'Email is required.'),
            new EmailConstraint(message: 'Invalid email.'),
            new Length(max: 180),
        ]);
        if (count($violations) > 0) {
            return $this->json([
                'error' => 'invalid_request',
                'details' => (string) $violations,
            ], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->users->findOneBy(['email' => $email]);
        $isNew = false;
        if (!$user instanceof User) {
            $user = new User();
            $user->setEmail($email);
            $user->setFirstName($this->getField($request, 'firstName') ?: null);
            $user->setLastName($this->getField($request, 'lastName') ?: null);
            $user->setMobileNumber($this->getField($request, 'mobileNumber') ?: null);
            $randomPassword = bin2hex(random_bytes(16));
            $user->setPassword($this->passwordHasher->hashPassword($user, $randomPassword));
            $this->em->persist($user);
            $this->em->flush();
            if ($user->getReference()) {
                $this->r2->ensureUserFolders($user->getReference());
            }
            $isNew = true;
        } else {
            $user->setFirstName($this->getField($request, 'firstName') ?: $user->getFirstName());
            $user->setLastName($this->getField($request, 'lastName') ?: $user->getLastName());
            $user->setMobileNumber($this->getField($request, 'mobileNumber') ?: $user->getMobileNumber());
            $this->em->flush();
        }

        $file = $request->files->get('photo');
        if ($file instanceof UploadedFile) {
            $att = $this->uploadUserPicProfile($file, $user);
            $user->setPicProfile($att);
            $user->addAttachment($att);
            $this->em->flush();
        }

        $result = $this->otpService->requestCode($email, $request->getClientIp());

        if ($isNew) {
            $welcome = (new TemplatedEmail())
                ->from('no-reply@neoodriver.test')
                ->to($email)
                ->subject('Bienvenue sur NeoDriver')
                ->htmlTemplate('email/welcome.html.twig')
                ->textTemplate('email/welcome.txt.twig')
                ->context(['user' => $user]);
            $this->mailer->send($welcome);
        }

        $payload = [
            'status' => 'ok',
            'ttl' => 300,
            'cooldown' => $result['cooldown'],
            'code' => $result['code'],
        ];

        return $this->json($payload, Response::HTTP_ACCEPTED);
    }

    private function uploadUserPicProfile(UploadedFile $file, User $user): Attachment
    {
        $ref = (string) $user->getReference();
        $baseKey = AttachmentField::USER_PIC_PROFILE->key($ref, (int) $user->getId());
        $orig = (string) $file->getClientOriginalName();
        $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION) ?: 'bin');
        $mime = (string) ($file->getMimeType() ?: 'application/octet-stream');
        $key = $baseKey . '.' . $ext;
        $body = file_get_contents($file->getPathname());
        $this->r2->putObject($key, $body !== false ? $body : '', $mime);
        $att = new Attachment();
        $att->setFileName(basename($key));
        $att->setOriginalFileName($orig !== '' ? $orig : null);
        $att->setFilePath($ref . '/' . AttachmentField::USER_PIC_PROFILE->folder() . '/' . basename($key));
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
