<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\NeooFeeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NeooFeeRepository::class)]
#[ORM\HasLifecycleCallbacks]
class NeooFee
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 3)]
    private string $taux = '0';

    #[ORM\Column(type: 'float')]
    private float $start = 0.0;

    #[ORM\Column(type: 'float')]
    private float $end = 0.0;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTaux(): string
    {
        return $this->taux;
    }
    public function setTaux(string $v): self
    {
        $this->taux = $v;
        return $this;
    }

    public function getStart(): float
    {
        return $this->start;
    }
    public function setStart(float $d): self
    {
        $this->start = $d;
        return $this;
    }

    public function getEnd(): float
    {
        return $this->end;
    }
    public function setEnd(float $d): self
    {
        $this->end = $d;
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
