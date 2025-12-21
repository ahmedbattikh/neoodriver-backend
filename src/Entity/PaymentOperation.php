<?php
declare(strict_types=1);

namespace App\Entity;

use App\Repository\PaymentOperationRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PaymentOperationRepository::class)]
class PaymentOperation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Driver::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Driver $driver;

    #[ORM\Column(length: 50)]
    private string $integrationCode;

    #[ORM\Column(length: 50)]
    private string $operationType;

    #[ORM\Column(length: 10)]
    private string $direction;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 3)]
    private string $amount;

    #[ORM\Column(length: 3)]
    private string $currency = 'TND';

    #[ORM\Column(length: 20)]
    private string $status;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $externalReference = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $occurredAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

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

    public function getIntegrationCode(): string
    {
        return $this->integrationCode;
    }
    public function setIntegrationCode(string $integrationCode): self
    {
        $this->integrationCode = $integrationCode;
        return $this;
    }

    public function getOperationType(): string
    {
        return $this->operationType;
    }
    public function setOperationType(string $operationType): self
    {
        $this->operationType = $operationType;
        return $this;
    }

    public function getDirection(): string
    {
        return $this->direction;
    }
    public function setDirection(string $direction): self
    {
        $this->direction = $direction;
        return $this;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }
    public function setAmount(string $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }
    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }
    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getExternalReference(): ?string
    {
        return $this->externalReference;
    }
    public function setExternalReference(?string $externalReference): self
    {
        $this->externalReference = $externalReference;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getOccurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
    public function setOccurredAt(\DateTimeImmutable $occurredAt): self
    {
        $this->occurredAt = $occurredAt;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}

