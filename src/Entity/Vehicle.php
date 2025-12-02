<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\EnergyType;
use App\Repository\VehicleRepository;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;


#[ORM\Entity(repositoryClass: VehicleRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Vehicle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[JMS\Groups(['me:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Driver::class, inversedBy: 'vehicles')]
    #[ORM\JoinColumn(nullable: false)]
    private Driver $driver;

    #[ORM\Column(length: 32)]
    #[JMS\Groups(['me:read'])]
    private string $registrationNumber = '';

    #[ORM\Column(length: 64)]
    #[JMS\Groups(['me:read'])]
    private string $make = '';

    #[ORM\Column(length: 64)]
    #[JMS\Groups(['me:read'])]
    private string $model = '';

    #[ORM\Column]
    #[JMS\Groups(['me:read'])]
    private int $firstRegistrationYear = 0;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    #[JMS\Groups(['me:read'])]
    private ?\DateTimeImmutable $registrationDate = null;

    #[ORM\Column]
    #[JMS\Groups(['me:read'])]
    private int $seatCount = 0;

    #[ORM\Column(enumType: EnergyType::class)]
    #[JMS\Exclude]
    private EnergyType $energyType = EnergyType::OTHER;

    // Attachments
    #[ORM\ManyToOne(targetEntity: Attachment::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    #[JMS\Groups(['me:read'])]
    private ?Attachment $registrationCertificate = null;

    #[ORM\ManyToOne(targetEntity: Attachment::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    #[JMS\Groups(['me:read'])]
    private ?Attachment $paidTransportInsuranceCertificate = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    #[JMS\Groups(['me:read'])]
    private ?\DateTimeImmutable $insuranceExpirationDate = null;

    #[ORM\ManyToOne(targetEntity: Attachment::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    #[JMS\Groups(['me:read'])]
    private ?Attachment $technicalInspection = null;

    #[ORM\ManyToOne(targetEntity: Attachment::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    #[JMS\Groups(['me:read'])]
    private ?Attachment $vehicleFrontPhoto = null;

    #[ORM\ManyToOne(targetEntity: Attachment::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    #[JMS\Groups(['me:read'])]
    private ?Attachment $insuranceNote = null;

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

    public function getRegistrationNumber(): string
    {
        return $this->registrationNumber;
    }
    public function setRegistrationNumber(string $n): self
    {
        $this->registrationNumber = $n;
        return $this;
    }

    public function getMake(): string
    {
        return $this->make;
    }
    public function setMake(string $make): self
    {
        $this->make = $make;
        return $this;
    }

    public function getModel(): string
    {
        return $this->model;
    }
    public function setModel(string $model): self
    {
        $this->model = $model;
        return $this;
    }

    public function getFirstRegistrationYear(): int
    {
        return $this->firstRegistrationYear;
    }
    public function setFirstRegistrationYear(int $y): self
    {
        $this->firstRegistrationYear = $y;
        return $this;
    }

    public function getRegistrationDate(): ?\DateTimeImmutable
    {
        return $this->registrationDate;
    }
    public function setRegistrationDate(?\DateTimeImmutable $d): self
    {
        $this->registrationDate = $d;
        return $this;
    }

    public function getSeatCount(): int
    {
        return $this->seatCount;
    }
    public function setSeatCount(int $c): self
    {
        $this->seatCount = $c;
        return $this;
    }

    public function getEnergyType(): EnergyType
    {
        return $this->energyType;
    }
    public function setEnergyType(EnergyType $t): self
    {
        $this->energyType = $t;
        return $this;
    }

    #[JMS\VirtualProperty]
    #[JMS\SerializedName('energyType')]
    #[JMS\Groups(['me:read'])]
    public function getEnergyTypeValue(): string
    {
        return $this->energyType->value;
    }

    public function getRegistrationCertificate(): ?Attachment
    {
        return $this->registrationCertificate;
    }
    public function setRegistrationCertificate(?Attachment $a): self
    {
        $this->registrationCertificate = $a;
        return $this;
    }

    public function getPaidTransportInsuranceCertificate(): ?Attachment
    {
        return $this->paidTransportInsuranceCertificate;
    }
    public function setPaidTransportInsuranceCertificate(?Attachment $a): self
    {
        $this->paidTransportInsuranceCertificate = $a;
        return $this;
    }

    public function getInsuranceExpirationDate(): ?\DateTimeImmutable
    {
        return $this->insuranceExpirationDate;
    }
    public function setInsuranceExpirationDate(?\DateTimeImmutable $d): self
    {
        $this->insuranceExpirationDate = $d;
        return $this;
    }

    public function getTechnicalInspection(): ?Attachment
    {
        return $this->technicalInspection;
    }
    public function setTechnicalInspection(?Attachment $a): self
    {
        $this->technicalInspection = $a;
        return $this;
    }

    public function getVehicleFrontPhoto(): ?Attachment
    {
        return $this->vehicleFrontPhoto;
    }
    public function setVehicleFrontPhoto(?Attachment $a): self
    {
        $this->vehicleFrontPhoto = $a;
        return $this;
    }

    public function getInsuranceNote(): ?Attachment
    {
        return $this->insuranceNote;
    }
    public function setInsuranceNote(?Attachment $a): self
    {
        $this->insuranceNote = $a;
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
