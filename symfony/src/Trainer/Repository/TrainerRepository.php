<?php

namespace App\Trainer\Repository;

use App\Client\Entity\Client;
use App\Trainer\Entity\Trainer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Trainer>
 */
class TrainerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Trainer::class);
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
    public function create(Trainer $trainer): void
    {
        $this->getEntityManager()->persist($trainer);
        $this->save();
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function remove(Trainer $trainer): void
    {
        $this->getEntityManager()->remove($trainer);
        $this->save();
    }
    //    /**
    //     * @return OurTrainer[] Returns an array of OurTrainer objects
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

    //    public function findOneBySomeField($value): ?OurTrainer
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
