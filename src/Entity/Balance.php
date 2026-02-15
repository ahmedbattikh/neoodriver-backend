<?php
declare(strict_types=1);

namespace App\Entity;

use App\Repository\BalanceRepository;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;

#[ORM\Entity(repositoryClass: BalanceRepository::class)]
class Balance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[JMS\Groups(['me:read'])]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'balance')]
    #[ORM\JoinColumn(nullable: false, unique: true)]
    private Driver $driver;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 3)]
    #[JMS\Groups(['me:read'])]
    private string $sold = '0.000';

    #[ORM\Column(type: 'decimal', precision: 12, scale: 3)]
    #[JMS\Groups(['me:read'])]
    private string $soldConge = '0.000';

    #[ORM\Column(type: 'decimal', precision: 12, scale: 3)]
    #[JMS\Groups(['me:read'])]
    private string $totalDebit = '0.000';

    #[ORM\Column(type: 'datetime_immutable')]
    #[JMS\Groups(['me:read'])]
    private \DateTimeImmutable $lastUpdate;

    public function __construct()
    {
        $this->lastUpdate = new \DateTimeImmutable('now');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDriver(): Driver
    {
        return $this->driver;
    }
    public function setDriver(Driver $driver): self
    {
        $this->driver = $driver;
        return $this;
    }

    public function getSold(): string
    {
        return $this->sold;
    }
    public function setSold(string $sold): self
    {
        $this->sold = $sold;
        return $this;
    }

    public function getSoldConge(): string
    {
        return $this->soldConge;
    }
    public function setSoldConge(string $soldConge): self
    {
        $this->soldConge = $soldConge;
        return $this;
    }

    public function getTotalDebit(): string
    {
        return $this->totalDebit;
    }
    public function setTotalDebit(string $totalDebit): self
    {
        $this->totalDebit = $totalDebit;
        return $this;
    }

    public function getLastUpdate(): \DateTimeImmutable
    {
        return $this->lastUpdate;
    }
    public function setLastUpdate(\DateTimeImmutable $lastUpdate): self
    {
        $this->lastUpdate = $lastUpdate;
        return $this;
    }
}
