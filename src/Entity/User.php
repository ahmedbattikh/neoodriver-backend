<?php

namespace App\Entity;

use App\Repository\UserRepository;
use App\Enum\UserRole;
use JMS\Serializer\Annotation as JMS;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[ORM\HasLifecycleCallbacks]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[JMS\Groups(['me:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[JMS\Groups(['me:read'])]
    private ?string $email = null;

    #[ORM\Column(length: 32, unique: true, nullable: true)]
    #[JMS\Groups(['me:read'])]
    private ?string $reference = null;

    #[ORM\Column(length: 64, nullable: true)]
    #[JMS\Groups(['me:read'])]
    private ?string $firstName = null;

    #[ORM\Column(length: 64, nullable: true)]
    #[JMS\Groups(['me:read'])]
    private ?string $lastName = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    #[JMS\Groups(['me:read'])]
    private ?\DateTimeImmutable $birthDate = null;

    #[ORM\Column(length: 128, nullable: true)]
    #[JMS\Groups(['me:read'])]
    private ?string $countryOfBirth = null;

    #[ORM\Column(length: 128, nullable: true)]
    #[JMS\Groups(['me:read'])]
    private ?string $cityOfBirth = null;

    #[ORM\Column(length: 64, nullable: true)]
    #[JMS\Groups(['me:read'])]
    private ?string $birthDepartment = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[JMS\Groups(['me:read'])]
    private ?string $address = null;

    #[ORM\Column(length: 128, nullable: true)]
    #[JMS\Groups(['me:read'])]
    private ?string $city = null;

    #[ORM\Column(length: 32, nullable: true)]
    #[JMS\Groups(['me:read'])]
    private ?string $mobileNumber = null;

    #[ORM\Column(enumType: UserRole::class, nullable: true)]
    private ?UserRole $role = null;

    #[ORM\ManyToOne(targetEntity: Attachment::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL', nullable: true)]
    #[JMS\Groups(['me:read'])]
    private ?Attachment $picProfile = null;

    #[ORM\OneToOne(mappedBy: 'user', cascade: ['persist', 'remove'])]
    private ?Driver $driverProfile = null;

    /** @var Collection<int, Attachment> */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Attachment::class, cascade: ['persist', 'remove'])]
    private Collection $attachments;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    #[JMS\Groups(['me:read'])]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): static
    {
        $this->reference = $reference;
        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): static
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): static
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getBirthDate(): ?\DateTimeImmutable
    {
        return $this->birthDate;
    }

    public function setBirthDate(?\DateTimeImmutable $birthDate): static
    {
        $this->birthDate = $birthDate;
        return $this;
    }

    public function getCountryOfBirth(): ?string
    {
        return $this->countryOfBirth;
    }

    public function setCountryOfBirth(?string $countryOfBirth): static
    {
        $this->countryOfBirth = $countryOfBirth;
        return $this;
    }

    public function getCityOfBirth(): ?string
    {
        return $this->cityOfBirth;
    }

    public function setCityOfBirth(?string $cityOfBirth): static
    {
        $this->cityOfBirth = $cityOfBirth;
        return $this;
    }

    public function getBirthDepartment(): ?string
    {
        return $this->birthDepartment;
    }

    public function setBirthDepartment(?string $birthDepartment): static
    {
        $this->birthDepartment = $birthDepartment;
        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): static
    {
        $this->address = $address;
        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(?string $city): static
    {
        $this->city = $city;
        return $this;
    }

    public function getMobileNumber(): ?string
    {
        return $this->mobileNumber;
    }

    public function setMobileNumber(?string $mobileNumber): static
    {
        $this->mobileNumber = $mobileNumber;
        return $this;
    }

    public function getRole(): ?UserRole
    {
        return $this->role;
    }

    public function setRole(?UserRole $role): static
    {
        $this->role = $role;
        return $this;
    }

    public function getPicProfile(): ?Attachment
    {
        return $this->picProfile;
    }

    public function setPicProfile(?Attachment $attachment): static
    {
        $this->picProfile = $attachment;
        return $this;
    }

    public function getDriverProfile(): ?Driver
    {
        return $this->driverProfile;
    }

    public function setDriverProfile(?Driver $driver): static
    {
        $this->driverProfile = $driver;
        if ($driver && $driver->getUser() !== $this) {
            $driver->setUser($this);
        }
        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    #[JMS\VirtualProperty]
    #[JMS\SerializedName('role')]
    #[JMS\Groups(['me:read'])]
    public function getRoleValue(): ?string
    {
        return $this->role?->value;
    }

    /** @return Collection<int, Attachment> */
    public function getAttachments(): Collection
    {
        return $this->attachments;
    }

    public function addAttachment(Attachment $attachment): static
    {
        if (!isset($this->attachments) || !$this->attachments->contains($attachment)) {
            $this->attachments->add($attachment);
            $attachment->setUser($this);
        }
        return $this;
    }

    public function removeAttachment(Attachment $attachment): static
    {
        if (isset($this->attachments) && $this->attachments->removeElement($attachment)) {
            if ($attachment->getUser() === $this) {
                $attachment->setUser(null);
            }
        }
        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);

        return $data;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        if (!isset($this->attachments)) {
            $this->attachments = new ArrayCollection();
        }
        if ($this->reference === null) {
            $this->reference = $this->generateReference();
        }
    }

    private function generateReference(): string
    {
        return 'AU-' . strtoupper(bin2hex(random_bytes(4)));
    }
}
