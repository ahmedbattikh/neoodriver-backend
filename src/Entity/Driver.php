<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\DriverClass;
use App\Repository\DriverRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;



#[ORM\Entity(repositoryClass: DriverRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Driver
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[JMS\Groups(['me:read'])]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'driverProfile')]
    #[ORM\JoinColumn(nullable: false, unique: true)]
    private User $user;

    #[ORM\Column]
    #[JMS\Groups(['me:read'])]
    private bool $active = true;

    #[ORM\Column(enumType: DriverClass::class)]
    private DriverClass $classDriver = DriverClass::CLASS1;

    #[ORM\Column(type: 'datetime_immutable')]
    #[JMS\Groups(['me:read'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    #[JMS\Groups(['me:read'])]
    private \DateTimeImmutable $updatedAt;

    #[ORM\OneToOne(mappedBy: 'driver', cascade: ['persist', 'remove'])]
    #[JMS\Groups(['me:read'])]
    private ?DriverDocuments $documents = null;

    #[ORM\OneToOne(mappedBy: 'driver', cascade: ['persist', 'remove'])]
    #[JMS\Groups(['me:read'])]
    private ?CompanyDocuments $companyDocuments = null;

    /** @var Collection<int, Vehicle> */
    #[ORM\OneToMany(mappedBy: 'driver', targetEntity: Vehicle::class, cascade: ['persist', 'remove'])]
    #[JMS\Groups(['me:read'])]
    private Collection $vehicles;

    /** @var Collection<int, DriverIntegrationAccount> */
    #[ORM\OneToMany(mappedBy: 'driver', targetEntity: DriverIntegrationAccount::class, cascade: ['persist', 'remove'])]
    #[JMS\Groups(['me:read'])]
    private Collection $integrationAccounts;

    public function __construct()
    {
        $this->vehicles = new ArrayCollection();
        $this->integrationAccounts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;
        return $this;
    }

    public function getClassDriver(): string
    {
        return $this->classDriver->value;
    }
    public function setClassDriver(string $class): self
    {
        $val = strtolower($class);
        $this->classDriver = DriverClass::tryFrom($val) ?? DriverClass::CLASS1;
        return $this;
    }
    public function getClassDriverEnum(): DriverClass
    {
        return $this->classDriver;
    }
    public function setClassDriverEnum(DriverClass $class): self
    {
        $this->classDriver = $class;
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

    public function getDocuments(): ?DriverDocuments
    {
        return $this->documents;
    }

    public function setDocuments(?DriverDocuments $documents): self
    {
        $this->documents = $documents;
        if ($documents && $documents->getDriver() !== $this) {
            $documents->setDriver($this);
        }
        return $this;
    }

    public function getCompanyDocuments(): ?CompanyDocuments
    {
        return $this->companyDocuments;
    }

    public function setCompanyDocuments(?CompanyDocuments $companyDocuments): self
    {
        $this->companyDocuments = $companyDocuments;
        if ($companyDocuments && $companyDocuments->getDriver() !== $this) {
            $companyDocuments->setDriver($this);
        }
        return $this;
    }

    /** @return Collection<int, Vehicle> */
    public function getVehicles(): Collection
    {
        return $this->vehicles;
    }

    public function addVehicle(Vehicle $vehicle): self
    {
        if (!$this->vehicles->contains($vehicle)) {
            $this->vehicles->add($vehicle);
            $vehicle->setDriver($this);
        }
        return $this;
    }

    public function removeVehicle(Vehicle $vehicle): self
    {
        if ($this->vehicles->removeElement($vehicle)) {
            if ($vehicle->getDriver() === $this) {
                $vehicle->setDriver($this);
            }
        }
        return $this;
    }

    /** @return Collection<int, DriverIntegrationAccount> */
    public function getIntegrationAccounts(): Collection
    {
        return $this->integrationAccounts;
    }
    public function addIntegrationAccount(DriverIntegrationAccount $acc): self
    {
        if (!$this->integrationAccounts->contains($acc)) {
            $this->integrationAccounts->add($acc);
            $acc->setDriver($this);
        }
        return $this;
    }
    public function removeIntegrationAccount(DriverIntegrationAccount $acc): self
    {
        $this->integrationAccounts->removeElement($acc);
        return $this;
    }

    #[ORM\OneToOne(mappedBy: 'driver', cascade: ['persist', 'remove'])]
    #[JMS\Groups(['me:read'])]
    private ?Balance $balance = null;

    public function getBalance(): ?Balance
    {
        return $this->balance;
    }
    public function setBalance(?Balance $balance): self
    {
        $this->balance = $balance;
        if ($balance && $balance->getDriver() !== $this) {
            $balance->setDriver($this);
        }
        return $this;
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
