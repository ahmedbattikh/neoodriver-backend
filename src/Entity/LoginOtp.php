<?php
declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Repository\LoginOtpRepository::class)]
#[ORM\Table(name: 'login_otps')]
class LoginOtp
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private string $email;

    #[ORM\Column(length: 255)]
    private string $codeHash;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $consumedAt = null;

    #[ORM\Column]
    private int $attempts = 0;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $ipAddress = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct(string $email, string $codeHash, \DateTimeImmutable $expiresAt, ?string $ipAddress = null)
    {
        $this->email = $email;
        $this->codeHash = $codeHash;
        $this->expiresAt = $expiresAt;
        $this->ipAddress = $ipAddress;
        $this->createdAt = new \DateTimeImmutable('now');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getCodeHash(): string
    {
        return $this->codeHash;
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function isExpired(\DateTimeImmutable $now = new \DateTimeImmutable('now')): bool
    {
        return $this->expiresAt <= $now;
    }

    public function getConsumedAt(): ?\DateTime
    {
        return $this->consumedAt;
    }

    public function consume(): void
    {
        $this->consumedAt = new \DateTime('now');
    }

    public function getAttempts(): int
    {
        return $this->attempts;
    }

    public function incrementAttempts(): void
    {
        $this->attempts++;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}