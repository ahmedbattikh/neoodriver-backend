<?php
declare(strict_types=1);

namespace App\Entity;

use App\Enum\AdvanceRequestStatus;
use App\Repository\AdvanceRequestRepository;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;

#[ORM\Entity(repositoryClass: AdvanceRequestRepository::class)]
#[ORM\HasLifecycleCallbacks]
class AdvanceRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[JMS\Groups(['me:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Driver::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Driver $driver;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 3)]
    #[JMS\Groups(['me:read'])]
    private string $amount;

    #[ORM\Column(enumType: AdvanceRequestStatus::class)]
    #[JMS\Groups(['me:read'])]
    private AdvanceRequestStatus $status = AdvanceRequestStatus::PENDING;

    #[ORM\ManyToOne(targetEntity: Attachment::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL', nullable: true)]
    private ?Attachment $attachment = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[JMS\Groups(['me:read'])]
    private ?string $description = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $adminNote = null;

    #[ORM\Column(type: 'datetime_immutable')]
    #[JMS\Groups(['me:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    #[JMS\Groups(['me:read'])]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    #[JMS\Groups(['me:read'])]
    private ?\DateTimeImmutable $approvedAt = null;

    public function __construct()
    {
        $now = new \DateTimeImmutable('now');
        $this->createdAt = $now;
        $this->updatedAt = $now;
        $this->amount = '0.000';
    }

    public function getId(): ?int { return $this->id; }
    public function getDriver(): Driver { return $this->driver; }
    public function setDriver(Driver $driver): self { $this->driver = $driver; return $this; }

    public function getAmount(): string { return $this->amount; }
    public function setAmount(string $amount): self { $this->amount = $amount; return $this; }

    public function getStatus(): AdvanceRequestStatus { return $this->status; }
    public function setStatus(AdvanceRequestStatus $s): self { $this->status = $s; return $this; }

    public function getAttachment(): ?Attachment { return $this->attachment; }
    public function setAttachment(?Attachment $a): self { $this->attachment = $a; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $d): self { $this->description = $d; return $this; }

    public function getAdminNote(): ?string { return $this->adminNote; }
    public function setAdminNote(?string $n): self { $this->adminNote = $n; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void { $this->updatedAt = new \DateTimeImmutable('now'); }

    public function getApprovedAt(): ?\DateTimeImmutable { return $this->approvedAt; }
    public function setApprovedAt(?\DateTimeImmutable $t): self { $this->approvedAt = $t; return $this; }
}
