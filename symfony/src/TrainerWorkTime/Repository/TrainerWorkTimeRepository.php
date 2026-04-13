<?php

namespace App\TrainerWorkTime\Repository;

use App\TrainerWorkTime\Entity\TrainerWorkTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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

    public function create(TrainerWorkTime $trainerWorktime): void
    {
        $this->getEntityManager()->persist($trainerWorktime);
    }

    public function remove(TrainerWorkTime $trainerWorktime): void
    {
        $this->getEntityManager()->remove($trainerWorktime);
    }
}
