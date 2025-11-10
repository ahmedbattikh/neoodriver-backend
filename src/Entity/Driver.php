<?php
declare(strict_types=1);

namespace App\Entity;

use App\Repository\DriverRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DriverRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Driver
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'driverProfile')]
    #[ORM\JoinColumn(nullable: false, unique: true)]
    private User $user;

    #[ORM\Column]
    private bool $active = true;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\OneToOne(mappedBy: 'driver', cascade: ['persist', 'remove'])]
    private ?DriverDocuments $documents = null;

    #[ORM\OneToOne(mappedBy: 'driver', cascade: ['persist', 'remove'])]
    private ?CompanyDocuments $companyDocuments = null;

    /** @var Collection<int, Vehicle> */
    #[ORM\OneToMany(mappedBy: 'driver', targetEntity: Vehicle::class, cascade: ['persist', 'remove'])]
    private Collection $vehicles;

    public function __construct()
    {
        $this->vehicles = new ArrayCollection();
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