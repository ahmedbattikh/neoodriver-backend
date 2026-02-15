<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\AdvanceRequest;
use App\Entity\Attachment;
use App\Entity\PaymentOperation;
use App\Enum\AdvanceRequestStatus;
use App\Enum\AttachmentField;
use App\Enum\AttachmentType;
use App\Enum\PaymentMethodType;
use App\Service\Storage\R2Client;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[IsGranted('ROLE_SUPER_ADMIN')]
final class AdvanceRequestAdminController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em, private readonly R2Client $r2) {}

    #[Route('/admin/advance-requests/{id}/approve', name: 'admin_advance_request_approve', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function approve(Request $request, int $id): Response
    {
        $req = $this->em->getRepository(AdvanceRequest::class)->find($id);
        if (!$req instanceof AdvanceRequest) {
            return $this->redirectToRoute('admin_dashboard');
        }
        $driver = $req->getDriver();
        $user = $driver->getUser();
        $token = (string) $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid('approve_advance_' . $req->getId(), $token)) {
            return $this->redirectToRoute('admin_user_show', ['id' => $user?->getId()]);
        }
        /** @var UploadedFile|null $file */
        $file = $request->files->get('proof');
        $att = null;
        if ($file instanceof UploadedFile) {
            $att = $this->uploadAttachment($file, AttachmentField::EXPENSE_INVOICE, $user, (int) $req->getId());
        }
        $req->setAttachment($att);
        $req->setStatus(AdvanceRequestStatus::APPROVED);
        $req->setApprovedAt(new \DateTimeImmutable('now'));

        $op = new PaymentOperation();
        $op->setDriver($driver);
        $op->setIntegrationCode('MANUAL');
        $op->setOperationType('CASH_ADVANCE');
        $op->setDirection('OUT');
        $op->setAmount($req->getAmount());
        $op->setCurrency('TND');
        $op->setPaymentMethodEnum(PaymentMethodType::CASH);
        $op->setBonus('0.000');
        $op->setTips('0.000');
        $op->setStatus('completed');
        $op->setExternalReference('ADV-' . $req->getId());
        $op->setDescription('Cash advance approval');
        $op->setOccurredAt(new \DateTimeImmutable('now'));

        $this->em->persist($op);
        $this->em->flush();
        return $this->redirectToRoute('admin_user_show', ['id' => $user?->getId()]);
    }

    #[Route('/admin/advance-requests/{id}/reject', name: 'admin_advance_request_reject', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function reject(Request $request, int $id): Response
    {
        $req = $this->em->getRepository(AdvanceRequest::class)->find($id);
        if (!$req instanceof AdvanceRequest) {
            return $this->redirectToRoute('admin_dashboard');
        }
        $driver = $req->getDriver();
        $user = $driver->getUser();
        $token = (string) $request->request->get('_token', '');
        if (!$this->isCsrfTokenValid('reject_advance_' . $req->getId(), $token)) {
            return $this->redirectToRoute('admin_user_show', ['id' => $user?->getId()]);
        }
        $note = (string) $request->request->get('admin_note', '');
        $req->setAdminNote($note !== '' ? $note : null);
        $req->setStatus(AdvanceRequestStatus::REJECTED);
        $req->setApprovedAt(null);
        $this->em->flush();
        return $this->redirectToRoute('admin_user_show', ['id' => $user?->getId()]);
    }

    private function uploadAttachment(UploadedFile $file, AttachmentField $af, \App\Entity\User $user, int $recordId): Attachment
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
        $att->setType(AttachmentType::OTHER);
        $att->setIsPrivate(true);
        $att->setIsActive(true);
        $att->setChecksum(hash_file('sha256', $file->getPathname()));
        $att->setUser($user);
        $this->em->persist($att);
        return $att;
    }
}
