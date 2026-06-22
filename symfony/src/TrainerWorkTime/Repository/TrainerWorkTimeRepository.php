<?php

declare(strict_types=1);

namespace App\TrainerWorkTime\Repository;

use App\Trainer\Entity\Trainer;
use App\TrainerWorkTime\Entity\TrainerWorkTime;
use App\Training\Entity\Training;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TrainerWorkTime>
 */
final class TrainerWorkTimeRepository extends ServiceEntityRepository
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

    public function findByDateForTrainer(Trainer $trainer, DateTimeImmutable $date): ?TrainerWorkTime
    {
        /** @var TrainerWorkTime|null $result */
        $result = $this->createQueryBuilder('wt')
            ->where('wt.trainer = :trainer')
            ->andWhere('wt.date = :date')
            ->setParameter('trainer', $trainer)
            ->setParameter('date', $date)
            ->getQuery()
            ->getOneOrNullResult();

        return $result;
    }

    public function findForUpdate(int $id): ?TrainerWorkTime
    {
        /** @var TrainerWorkTime|null $worktime */
        $worktime = $this->createQueryBuilder('worktime')
            ->andWhere('worktime.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->setLockMode(LockMode::PESSIMISTIC_WRITE)
            ->getOneOrNullResult();

        return $worktime;
    }
}
