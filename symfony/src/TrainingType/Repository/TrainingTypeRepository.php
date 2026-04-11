<?php

namespace App\TrainingType\Repository;

use App\TrainingType\Entity\TrainingType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TrainingType>
 */
class TrainingTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TrainingType::class);
    }

    public function create(TrainingType $trainingType): void
    {
        $this->getEntityManager()->persist($trainingType);
    }

    public function remove(TrainingType $trainingType): void
    {
        $this->getEntityManager()->remove($trainingType);
    }
}
