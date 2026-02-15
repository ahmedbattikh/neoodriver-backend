<?php
declare(strict_types=1);

namespace App\Entity;

use App\Repository\NeooConfigRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NeooConfigRepository::class)]
#[ORM\HasLifecycleCallbacks]
class NeooConfig
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 3)]
    private string $fixNeooMonthly = '0';

    #[ORM\Column(type: 'decimal', precision: 12, scale: 3)]
    private string $tauxConge = '0';

    #[ORM\Column(type: 'decimal', precision: 12, scale: 3)]
    private string $fraisKm = '0';

    #[ORM\Column(type: 'decimal', precision: 12, scale: 3)]
    private string $tauxPas = '0';

    #[ORM\Column(type: 'decimal', precision: 12, scale: 3)]
    private string $tauxUrssaf = '0';

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFixNeooMonthly(): string
    {
        return $this->fixNeooMonthly;
    }
    public function setFixNeooMonthly(string $v): self
    {
        $this->fixNeooMonthly = $v;
        return $this;
    }

    public function getTauxConge(): string
    {
        return $this->tauxConge;
    }
    public function setTauxConge(string $v): self
    {
        $this->tauxConge = $v;
        return $this;
    }

    public function getFraisKm(): string
    {
        return $this->fraisKm;
    }
    public function setFraisKm(string $v): self
    {
        $this->fraisKm = $v;
        return $this;
    }

    public function getTauxPas(): string
    {
        return $this->tauxPas;
    }
    public function setTauxPas(string $v): self
    {
        $this->tauxPas = $v;
        return $this;
    }

    public function getTauxUrssaf(): string
    {
        return $this->tauxUrssaf;
    }
    public function setTauxUrssaf(string $v): self
    {
        $this->tauxUrssaf = $v;
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
