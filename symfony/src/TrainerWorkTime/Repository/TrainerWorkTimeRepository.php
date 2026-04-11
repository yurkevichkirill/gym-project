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

    public function create(TrainerWorkTime $trainerAvailability): void
    {
        $this->getEntityManager()->persist($trainerAvailability);
    }

    public function remove(TrainerWorkTime $trainerAvailability): void
    {
        $this->getEntityManager()->remove($trainerAvailability);
    }
}
