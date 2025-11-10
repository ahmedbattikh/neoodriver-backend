<?php
declare(strict_types=1);

namespace App\Entity;

use App\Repository\CompanyDocumentsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CompanyDocumentsRepository::class)]
#[ORM\HasLifecycleCallbacks]
class CompanyDocuments
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'companyDocuments')]
    #[ORM\JoinColumn(nullable: false, unique: true)]
    private Driver $driver;

    // Company attachments
    #[ORM\ManyToOne(targetEntity: Attachment::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?Attachment $employmentContract = null;

    #[ORM\ManyToOne(targetEntity: Attachment::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?Attachment $employerCertificate = null;

    #[ORM\ManyToOne(targetEntity: Attachment::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?Attachment $preEmploymentDeclaration = null;

    #[ORM\ManyToOne(targetEntity: Attachment::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?Attachment $mutualInsuranceCertificate = null;

    #[ORM\ManyToOne(targetEntity: Attachment::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?Attachment $urssafComplianceCertificate = null;

    #[ORM\ManyToOne(targetEntity: Attachment::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?Attachment $kbisExtract = null;

    #[ORM\ManyToOne(targetEntity: Attachment::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?Attachment $revtcRegistrationCertificate = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function getId(): ?int { return $this->id; }

    public function getDriver(): Driver { return $this->driver; }
    public function setDriver(Driver $driver): self { $this->driver = $driver; if ($driver->getCompanyDocuments() !== $this) { $driver->setCompanyDocuments($this); } return $this; }

    public function getEmploymentContract(): ?Attachment { return $this->employmentContract; }
    public function setEmploymentContract(?Attachment $a): self { $this->employmentContract = $a; return $this; }

    public function getEmployerCertificate(): ?Attachment { return $this->employerCertificate; }
    public function setEmployerCertificate(?Attachment $a): self { $this->employerCertificate = $a; return $this; }

    public function getPreEmploymentDeclaration(): ?Attachment { return $this->preEmploymentDeclaration; }
    public function setPreEmploymentDeclaration(?Attachment $a): self { $this->preEmploymentDeclaration = $a; return $this; }

    public function getMutualInsuranceCertificate(): ?Attachment { return $this->mutualInsuranceCertificate; }
    public function setMutualInsuranceCertificate(?Attachment $a): self { $this->mutualInsuranceCertificate = $a; return $this; }

    public function getUrssafComplianceCertificate(): ?Attachment { return $this->urssafComplianceCertificate; }
    public function setUrssafComplianceCertificate(?Attachment $a): self { $this->urssafComplianceCertificate = $a; return $this; }

    public function getKbisExtract(): ?Attachment { return $this->kbisExtract; }
    public function setKbisExtract(?Attachment $a): self { $this->kbisExtract = $a; return $this; }

    public function getRevtcRegistrationCertificate(): ?Attachment { return $this->revtcRegistrationCertificate; }
    public function setRevtcRegistrationCertificate(?Attachment $a): self { $this->revtcRegistrationCertificate = $a; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }

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