<?php
declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;

#[ORM\Entity]
class DriverIntegrationAccount
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[JMS\Groups(['me:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Driver::class, inversedBy: 'integrationAccounts')]
    #[ORM\JoinColumn(nullable: false)]
    private Driver $driver;

    #[ORM\ManyToOne(targetEntity: DriverIntegration::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[JMS\Groups(['me:read'])]
    private DriverIntegration $integration;

    #[ORM\Column(length: 128)]
    #[JMS\Groups(['me:read'])]
    private string $idDriver;

    #[ORM\Column(type: 'datetime_immutable')]
    #[JMS\Groups(['me:read'])]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable('now');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDriver(): Driver
    {
        return $this->driver;
    }
    public function setDriver(Driver $d): self
    {
        $this->driver = $d;
        return $this;
    }

    public function getIntegration(): DriverIntegration
    {
        return $this->integration;
    }
    public function setIntegration(DriverIntegration $i): self
    {
        $this->integration = $i;
        return $this;
    }

    public function getIdDriver(): string
    {
        return $this->idDriver;
    }
    public function setIdDriver(string $id): self
    {
        $this->idDriver = $id;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}

