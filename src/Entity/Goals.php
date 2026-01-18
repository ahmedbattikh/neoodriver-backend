<?php
declare(strict_types=1);

namespace App\Entity;

use App\Enum\GoalFrequency;
use App\Repository\GoalsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GoalsRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Goals
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $name = '';

    #[ORM\Column(type: 'decimal', precision: 12, scale: 3)]
    private string $amount;

    #[ORM\Column(enumType: GoalFrequency::class)]
    private GoalFrequency $frequency = GoalFrequency::WEEKLY;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $targetClasses = [];

    #[ORM\Column]
    private bool $enabled = true;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable('now');
        $this->updatedAt = new \DateTimeImmutable('now');
        $this->amount = '0.000';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }
    public function setName(string $name): self
    {
        $this->name = $name;
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

    public function getFrequency(): string
    {
        return $this->frequency->value;
    }
    public function setFrequency(string $frequency): self
    {
        $val = strtoupper($frequency);
        $this->frequency = GoalFrequency::tryFrom($val) ?? GoalFrequency::WEEKLY;
        return $this;
    }
    public function getFrequencyEnum(): GoalFrequency
    {
        return $this->frequency;
    }
    public function setFrequencyEnum(GoalFrequency $frequency): self
    {
        $this->frequency = $frequency;
        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }
    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;
        return $this;
    }

    public function getTargetClasses(): array
    {
        return $this->targetClasses ?? [];
    }
    public function setTargetClasses(array $classes): self
    {
        $normalized = [];
        foreach ($classes as $c) {
            if (is_string($c)) {
                $normalized[] = strtolower($c);
                continue;
            }
            if ($c instanceof \App\Enum\DriverClass) {
                $normalized[] = $c->value;
            }
        }
        $this->targetClasses = array_values(array_unique($normalized));
        return $this;
    }
    public function addTargetClass(\App\Enum\DriverClass $class): self
    {
        $arr = $this->getTargetClasses();
        $arr[] = $class->value;
        $this->targetClasses = array_values(array_unique($arr));
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

