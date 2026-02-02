<?php

namespace App\TrainerWorkTime\Repository;

use App\Client\Entity\Client;
use App\TrainerWorkTime\Entity\TrainerWorkTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TrainerWorkTime>
 */
class TrainerWorkTimeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TrainerWorkTime::class);
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
    public function create(TrainerWorkTime $trainerAvailability): void
    {
        $this->getEntityManager()->persist($trainerAvailability);
        $this->save();
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function remove(TrainerWorkTime $trainerAvailability): void
    {
        $this->getEntityManager()->remove($trainerAvailability);
        $this->save();
    }
    //    /**
    //     * @return TrainerWorkTime[] Returns an array of TrainerWorkTime objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('t.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?TrainerWorkTime
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
