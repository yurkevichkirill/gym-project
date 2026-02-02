<?php

namespace App\Training\Repository;

use App\Client\Entity\Client;
use App\Training\Entity\Training;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Training>
 */
class TrainingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Training::class);
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
    public function create(Training $training): void
    {
        $this->getEntityManager()->persist($training);
        $this->save();
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function remove(Training $training): void
    {
        $this->getEntityManager()->remove($training);
        $this->save();
    }

    //    /**
    //     * @return Training[] Returns an array of Training objects
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

    //    public function findOneBySomeField($value): ?Training
    //    {
    //        return $this->createQueryBuilder('t')
    //            ->andWhere('t.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
