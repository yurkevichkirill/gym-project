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

    public function create(Trainer $trainer): void
    {
        $this->getEntityManager()->persist($trainer);
    }

    public function remove(Trainer $trainer): void
    {
        $this->getEntityManager()->remove($trainer);
    }
}
