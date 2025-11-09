<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\LoginOtp;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LoginOtp>
 */
class LoginOtpRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LoginOtp::class);
    }

    public function save(LoginOtp $otp): void
    {
        $em = $this->getEntityManager();
        $em->persist($otp);
        $em->flush();
    }

    public function findLatestActiveByEmail(string $email): ?LoginOtp
    {
        $qb = $this->createQueryBuilder('o')
            ->andWhere('o.email = :email')
            ->andWhere('o.consumedAt IS NULL')
            ->andWhere('o.expiresAt > :now')
            ->setParameter('email', $email)
            ->setParameter('now', new \DateTimeImmutable('now'))
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }
}