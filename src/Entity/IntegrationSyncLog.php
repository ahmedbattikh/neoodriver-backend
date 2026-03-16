<?php
declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class IntegrationSyncLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 16)]
    private string $status = 'RUNNING';

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $startAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $endAt;

    #[ORM\Column]
    private int $hours = 2;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $startedAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $finishedAt = null;

    #[ORM\Column(nullable: true)]
    private ?int $totalOps = null;

    #[ORM\Column(nullable: true)]
    private ?int $syncedAccounts = null;

    #[ORM\Column(nullable: true)]
    private ?int $accountsTotal = null;

    #[ORM\Column(nullable: true)]
    private ?int $integrationsTotal = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $errorMessage = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $report = null;

    #[ORM\Column(length: 32)]
    private string $triggerType = 'command';

    #[ORM\Column(nullable: true)]
    private ?int $retryOfLogId = null;

    public function __construct()
    {
        $this->startedAt = new \DateTimeImmutable('now');
        $this->startAt = new \DateTimeImmutable('now');
        $this->endAt = new \DateTimeImmutable('now');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getStartAt(): \DateTimeImmutable
    {
        return $this->startAt;
    }

    public function setStartAt(\DateTimeImmutable $startAt): self
    {
        $this->startAt = $startAt;
        return $this;
    }

    public function getEndAt(): \DateTimeImmutable
    {
        return $this->endAt;
    }

    public function setEndAt(\DateTimeImmutable $endAt): self
    {
        $this->endAt = $endAt;
        return $this;
    }

    public function getHours(): int
    {
        return $this->hours;
    }

    public function setHours(int $hours): self
    {
        $this->hours = $hours;
        return $this;
    }

    public function getStartedAt(): \DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(\DateTimeImmutable $startedAt): self
    {
        $this->startedAt = $startedAt;
        return $this;
    }

    public function getFinishedAt(): ?\DateTimeImmutable
    {
        return $this->finishedAt;
    }

    public function setFinishedAt(?\DateTimeImmutable $finishedAt): self
    {
        $this->finishedAt = $finishedAt;
        return $this;
    }

    public function getTotalOps(): ?int
    {
        return $this->totalOps;
    }

    public function setTotalOps(?int $totalOps): self
    {
        $this->totalOps = $totalOps;
        return $this;
    }

    public function getSyncedAccounts(): ?int
    {
        return $this->syncedAccounts;
    }

    public function setSyncedAccounts(?int $syncedAccounts): self
    {
        $this->syncedAccounts = $syncedAccounts;
        return $this;
    }

    public function getAccountsTotal(): ?int
    {
        return $this->accountsTotal;
    }

    public function setAccountsTotal(?int $accountsTotal): self
    {
        $this->accountsTotal = $accountsTotal;
        return $this;
    }

    public function getIntegrationsTotal(): ?int
    {
        return $this->integrationsTotal;
    }

    public function setIntegrationsTotal(?int $integrationsTotal): self
    {
        $this->integrationsTotal = $integrationsTotal;
        return $this;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): self
    {
        $this->errorMessage = $errorMessage;
        return $this;
    }

    public function getReport(): ?array
    {
        return $this->report;
    }

    public function setReport(?array $report): self
    {
        $this->report = $report;
        return $this;
    }

    public function getTriggerType(): string
    {
        return $this->triggerType;
    }

    public function setTriggerType(string $triggerType): self
    {
        $this->triggerType = $triggerType;
        return $this;
    }

    public function getRetryOfLogId(): ?int
    {
        return $this->retryOfLogId;
    }

    public function setRetryOfLogId(?int $retryOfLogId): self
    {
        $this->retryOfLogId = $retryOfLogId;
        return $this;
    }
}
