<?php

namespace App\Membership\Repository;

use App\Client\Entity\Client;
use App\Membership\Entity\Membership;
use App\Membership\Enum\MembershipStatusEnum;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Membership>
 */
class MembershipRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Membership::class);
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function save(): void
    {
        $this->getEntityManager()->flush();
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function create(Membership $membership): void
    {
        $this->getEntityManager()->persist($membership);
        $this->save();
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function remove(Membership $membership): void
    {
        $this->getEntityManager()->remove($membership);
        $this->save();
    }

    public function findExpired(DateTimeImmutable $curDate): array
    {
        return $this->createQueryBuilder('m')
            ->where("m.status = :active")
            ->andWhere("m.endDate <= :curDate")
            ->setParameter("active", MembershipStatusEnum::ACTIVE)
            ->setParameter("curDate", $curDate)
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Membership[] Returns an array of Membership objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('m.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Membership
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
