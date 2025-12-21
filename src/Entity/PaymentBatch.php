<?php
declare(strict_types=1);

namespace App\Entity;

use App\Repository\PaymentBatchRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PaymentBatchRepository::class)]
class PaymentBatch
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: DriverIntegration::class)]
    #[ORM\JoinColumn(nullable: false)]
    private DriverIntegration $integration;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $periodStart;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $periodEnd;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 3)]
    private string $totalAmount;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIntegration(): DriverIntegration
    {
        return $this->integration;
    }
    public function setIntegration(DriverIntegration $integration): self
    {
        $this->integration = $integration;
        return $this;
    }

    public function getPeriodStart(): \DateTimeImmutable
    {
        return $this->periodStart;
    }
    public function setPeriodStart(\DateTimeImmutable $periodStart): self
    {
        $this->periodStart = $periodStart;
        return $this;
    }

    public function getPeriodEnd(): \DateTimeImmutable
    {
        return $this->periodEnd;
    }
    public function setPeriodEnd(\DateTimeImmutable $periodEnd): self
    {
        $this->periodEnd = $periodEnd;
        return $this;
    }

    public function getTotalAmount(): string
    {
        return $this->totalAmount;
    }
    public function setTotalAmount(string $totalAmount): self
    {
        $this->totalAmount = $totalAmount;
        return $this;
    }
}

