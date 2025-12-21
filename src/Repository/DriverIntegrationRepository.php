<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\DriverIntegration;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DriverIntegration>
 */
class DriverIntegrationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DriverIntegration::class);
    }
}

