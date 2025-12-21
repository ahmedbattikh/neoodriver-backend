<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\DriverIntegrationRepository;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;

#[ORM\Entity(repositoryClass: DriverIntegrationRepository::class)]
#[ORM\HasLifecycleCallbacks]
class DriverIntegration
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[JMS\Groups(['me:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 64, unique: true)]
    #[JMS\Groups(['me:read'])]
    private string $code = '';

    #[ORM\Column(length: 128)]
    #[JMS\Groups(['me:read'])]
    private string $name = '';

    #[ORM\Column(type: 'text', nullable: true)]
    #[JMS\Groups(['me:read'])]
    private ?string $description = null;

    #[JMS\Groups(['me:read'])]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $logoPath = null;

    #[ORM\Column]
    #[JMS\Groups(['me:read'])]
    private bool $enabled = true;

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

    public function getCode(): string
    {
        return $this->code;
    }
    public function setCode(string $code): self
    {
        $this->code = $code;
        return $this;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }
    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getLogoPath(): ?string
    {
        return $this->logoPath;
    }
    public function setLogoPath(?string $logoPath): self
    {
        $this->logoPath = $logoPath;
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
