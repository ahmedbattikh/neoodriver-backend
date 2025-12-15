<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\DriverDocumentsRepository;
use App\Enum\ValidationStatus;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;



#[ORM\Entity(repositoryClass: DriverDocumentsRepository::class)]
#[ORM\HasLifecycleCallbacks]
class DriverDocuments
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[JMS\Groups(['me:read'])]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'documents')]
    #[ORM\JoinColumn(nullable: false, unique: true)]
    private Driver $driver;

    // Identity photo
    #[ORM\ManyToOne(targetEntity: Attachment::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?Attachment $identityPhoto = null;

    // VTC card
    #[ORM\ManyToOne(targetEntity: Attachment::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    #[JMS\Groups(['me:read'])]
    private ?Attachment $vtcCardFront = null;

    #[ORM\ManyToOne(targetEntity: Attachment::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    #[JMS\Groups(['me:read'])]
    private ?Attachment $vtcCardBack = null;

    #[ORM\Column(enumType: ValidationStatus::class)]
    #[JMS\Groups(['me:read'])]
    private ValidationStatus $vtcCardValid = ValidationStatus::VALIDATION_INPROGRESS;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    #[JMS\Groups(['me:read'])]
    private ?\DateTimeImmutable $vtcCardExpirationDate = null;

    // Driving license
    #[ORM\ManyToOne(targetEntity: Attachment::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    #[JMS\Groups(['me:read'])]
    private ?Attachment $drivingLicenseFront = null;

    #[ORM\ManyToOne(targetEntity: Attachment::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    #[JMS\Groups(['me:read'])]
    private ?Attachment $drivingLicenseBack = null;

    #[ORM\Column(enumType: ValidationStatus::class)]
    #[JMS\Groups(['me:read'])]
    private ValidationStatus $drivingLicenseValid = ValidationStatus::VALIDATION_INPROGRESS;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    #[JMS\Groups(['me:read'])]
    private ?\DateTimeImmutable $drivingLicenseExpirationDate = null;

    // Identity card
    #[ORM\ManyToOne(targetEntity: Attachment::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    #[JMS\Groups(['me:read'])]
    private ?Attachment $identityCardFront = null;

    #[ORM\ManyToOne(targetEntity: Attachment::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    #[JMS\Groups(['me:read'])]
    private ?Attachment $identityCardBack = null;

    #[ORM\Column(enumType: ValidationStatus::class)]
    #[JMS\Groups(['me:read'])]
    private ValidationStatus $identityCardValid = ValidationStatus::VALIDATION_INPROGRESS;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    #[JMS\Groups(['me:read'])]
    private ?\DateTimeImmutable $identityCardExpirationDate = null;

    // Health card
    #[ORM\ManyToOne(targetEntity: Attachment::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    #[JMS\Groups(['me:read'])]
    private ?Attachment $healthCard = null;

    #[ORM\Column(enumType: ValidationStatus::class)]
    #[JMS\Groups(['me:read'])]
    private ValidationStatus $healthCardValid = ValidationStatus::VALIDATION_INPROGRESS;

    // Social security
    #[ORM\Column(length: 32, nullable: true)]
    #[JMS\Groups(['me:read'])]
    private ?string $socialSecurityNumber = null;

    // Bank statement
    #[ORM\ManyToOne(targetEntity: Attachment::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    #[JMS\Groups(['me:read'])]
    private ?Attachment $bankStatement = null;

    #[ORM\Column(enumType: ValidationStatus::class)]
    #[JMS\Groups(['me:read'])]
    private ValidationStatus $bankStatementValid = ValidationStatus::VALIDATION_INPROGRESS;

    #[ORM\Column(length: 34, nullable: true)]
    #[JMS\Groups(['me:read'])]
    private ?string $iban = null;

    #[ORM\Column]
    #[JMS\Groups(['me:read'])]
    private bool $isHosted = false;

    // Proof of residence
    #[ORM\ManyToOne(targetEntity: Attachment::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    #[JMS\Groups(['me:read'])]
    private ?Attachment $proofOfResidence = null;

    #[ORM\Column(enumType: ValidationStatus::class)]
    #[JMS\Groups(['me:read'])]
    private ValidationStatus $proofOfResidenceValid = ValidationStatus::VALIDATION_INPROGRESS;

    // Secure driving right certificate
    #[ORM\ManyToOne(targetEntity: Attachment::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    #[JMS\Groups(['me:read'])]
    private ?Attachment $secureDrivingRightCertificate = null;

    #[ORM\Column(enumType: ValidationStatus::class)]
    #[JMS\Groups(['me:read'])]
    private ValidationStatus $secureDrivingRightCertificateValid = ValidationStatus::VALIDATION_INPROGRESS;

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
        if ($driver->getDocuments() !== $this) {
            $driver->setDocuments($this);
        }
        return $this;
    }

    public function getIdentityPhoto(): ?Attachment
    {
        return $this->identityPhoto;
    }
    public function setIdentityPhoto(?Attachment $a): self
    {
        $this->identityPhoto = $a;
        return $this;
    }

    public function getVtcCardFront(): ?Attachment
    {
        return $this->vtcCardFront;
    }
    public function setVtcCardFront(?Attachment $a): self
    {
        $this->vtcCardFront = $a;
        return $this;
    }

    public function getVtcCardBack(): ?Attachment
    {
        return $this->vtcCardBack;
    }
    public function setVtcCardBack(?Attachment $a): self
    {
        $this->vtcCardBack = $a;
        return $this;
    }

    public function getVtcCardValid(): ValidationStatus
    {
        return $this->vtcCardValid;
    }
    public function setVtcCardValid(ValidationStatus $valid): self
    {
        $this->vtcCardValid = $valid;
        return $this;
    }

    public function getVtcCardExpirationDate(): ?\DateTimeImmutable
    {
        return $this->vtcCardExpirationDate;
    }
    public function setVtcCardExpirationDate(?\DateTimeImmutable $d): self
    {
        $this->vtcCardExpirationDate = $d;
        return $this;
    }

    public function getDrivingLicenseFront(): ?Attachment
    {
        return $this->drivingLicenseFront;
    }
    public function setDrivingLicenseFront(?Attachment $a): self
    {
        $this->drivingLicenseFront = $a;
        return $this;
    }

    public function getDrivingLicenseBack(): ?Attachment
    {
        return $this->drivingLicenseBack;
    }
    public function setDrivingLicenseBack(?Attachment $a): self
    {
        $this->drivingLicenseBack = $a;
        return $this;
    }

    public function getDrivingLicenseValid(): ValidationStatus
    {
        return $this->drivingLicenseValid;
    }
    public function setDrivingLicenseValid(ValidationStatus $valid): self
    {
        $this->drivingLicenseValid = $valid;
        return $this;
    }

    public function getDrivingLicenseExpirationDate(): ?\DateTimeImmutable
    {
        return $this->drivingLicenseExpirationDate;
    }
    public function setDrivingLicenseExpirationDate(?\DateTimeImmutable $d): self
    {
        $this->drivingLicenseExpirationDate = $d;
        return $this;
    }

    public function getIdentityCardFront(): ?Attachment
    {
        return $this->identityCardFront;
    }
    public function setIdentityCardFront(?Attachment $a): self
    {
        $this->identityCardFront = $a;
        return $this;
    }

    public function getIdentityCardBack(): ?Attachment
    {
        return $this->identityCardBack;
    }
    public function setIdentityCardBack(?Attachment $a): self
    {
        $this->identityCardBack = $a;
        return $this;
    }

    public function getIdentityCardValid(): ValidationStatus
    {
        return $this->identityCardValid;
    }
    public function setIdentityCardValid(ValidationStatus $valid): self
    {
        $this->identityCardValid = $valid;
        return $this;
    }

    public function getIdentityCardExpirationDate(): ?\DateTimeImmutable
    {
        return $this->identityCardExpirationDate;
    }
    public function setIdentityCardExpirationDate(?\DateTimeImmutable $d): self
    {
        $this->identityCardExpirationDate = $d;
        return $this;
    }

    public function getHealthCard(): ?Attachment
    {
        return $this->healthCard;
    }
    public function setHealthCard(?Attachment $a): self
    {
        $this->healthCard = $a;
        return $this;
    }

    public function getHealthCardValid(): ValidationStatus
    {
        return $this->healthCardValid;
    }
    public function setHealthCardValid(ValidationStatus $valid): self
    {
        $this->healthCardValid = $valid;
        return $this;
    }

    public function getSocialSecurityNumber(): ?string
    {
        return $this->socialSecurityNumber;
    }
    public function setSocialSecurityNumber(?string $ssn): self
    {
        $this->socialSecurityNumber = $ssn;
        return $this;
    }

    public function getBankStatement(): ?Attachment
    {
        return $this->bankStatement;
    }
    public function setBankStatement(?Attachment $a): self
    {
        $this->bankStatement = $a;
        return $this;
    }

    public function getBankStatementValid(): ValidationStatus
    {
        return $this->bankStatementValid;
    }
    public function setBankStatementValid(ValidationStatus $valid): self
    {
        $this->bankStatementValid = $valid;
        return $this;
    }

    public function getIban(): ?string
    {
        return $this->iban;
    }
    public function setIban(?string $iban): self
    {
        $this->iban = $iban;
        return $this;
    }

    public function isHosted(): bool
    {
        return $this->isHosted;
    }
    public function setIsHosted(bool $hosted): self
    {
        $this->isHosted = $hosted;
        return $this;
    }

    public function getProofOfResidence(): ?Attachment
    {
        return $this->proofOfResidence;
    }
    public function setProofOfResidence(?Attachment $a): self
    {
        $this->proofOfResidence = $a;
        return $this;
    }

    public function getProofOfResidenceValid(): ValidationStatus
    {
        return $this->proofOfResidenceValid;
    }
    public function setProofOfResidenceValid(ValidationStatus $valid): self
    {
        $this->proofOfResidenceValid = $valid;
        return $this;
    }

    public function getSecureDrivingRightCertificate(): ?Attachment
    {
        return $this->secureDrivingRightCertificate;
    }
    public function setSecureDrivingRightCertificate(?Attachment $a): self
    {
        $this->secureDrivingRightCertificate = $a;
        return $this;
    }

    public function getSecureDrivingRightCertificateValid(): ValidationStatus
    {
        return $this->secureDrivingRightCertificateValid;
    }
    public function setSecureDrivingRightCertificateValid(ValidationStatus $valid): self
    {
        $this->secureDrivingRightCertificateValid = $valid;
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
