<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\ExpenseNoteType;
use App\Repository\ExpenseNoteRepository;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;

#[ORM\Entity(repositoryClass: ExpenseNoteRepository::class)]
#[ORM\HasLifecycleCallbacks]
class ExpenseNote
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[JMS\Groups(['me:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Driver::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Driver $driver;

    #[ORM\Column(type: 'date_immutable')]
    #[JMS\Groups(['me:read'])]
    private \DateTimeImmutable $noteDate;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 3)]
    #[JMS\Groups(['me:read'])]
    private string $amountTtc;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 3, nullable: true)]
    #[JMS\Groups(['me:read'])]
    private ?string $vat = null;

    #[ORM\Column(enumType: ExpenseNoteType::class)]
    #[JMS\Exclude]
    private ExpenseNoteType $type = ExpenseNoteType::OTHER;

    #[ORM\ManyToOne(targetEntity: Attachment::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    #[JMS\Groups(['me:read'])]
    private ?Attachment $invoice = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $valide = null;

    #[ORM\Column(type: 'datetime_immutable')]
    #[JMS\Groups(['me:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    #[JMS\Groups(['me:read'])]
    private \DateTimeImmutable $updatedAt;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDriver(): Driver
    {
        return $this->driver;
    }
    public function setDriver(Driver $driver): self
    {
        $this->driver = $driver;
        return $this;
    }

    public function getNoteDate(): \DateTimeImmutable
    {
        return $this->noteDate;
    }
    public function setNoteDate(\DateTimeImmutable $d): self
    {
        $this->noteDate = $d;
        return $this;
    }

    public function getAmountTtc(): string
    {
        return $this->amountTtc;
    }
    public function setAmountTtc(string $a): self
    {
        $this->amountTtc = $a;
        return $this;
    }

    public function getVat(): ?string
    {
        return $this->vat;
    }
    public function setVat(?string $v): self
    {
        $this->vat = $v;
        return $this;
    }

    public function getType(): string
    {
        return $this->type->value;
    }
    public function setType(string $t): self
    {
        $val = strtoupper($t);
        $this->type = ExpenseNoteType::tryFrom($val) ?? ExpenseNoteType::OTHER;
        return $this;
    }
    public function getTypeEnum(): ExpenseNoteType
    {
        return $this->type;
    }
    public function setTypeEnum(ExpenseNoteType $t): self
    {
        $this->type = $t;
        return $this;
    }
    #[JMS\VirtualProperty]
    #[JMS\SerializedName('type')]
    #[JMS\Groups(['me:read'])]
    public function getTypeValue(): string
    {
        return $this->type->value;
    }

    public function getInvoice(): ?Attachment
    {
        return $this->invoice;
    }
    public function setInvoice(?Attachment $a): self
    {
        $this->invoice = $a;
        return $this;
    }

    public function getValide(): ?bool
    {
        return $this->valide;
    }
    public function setValide(?bool $v): self
    {
        $this->valide = $v;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $now = new \DateTimeImmutable('now');
        $this->createdAt = $this->createdAt ?? $now;
        $this->updatedAt = $this->updatedAt ?? $now;
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable('now');
    }
}
