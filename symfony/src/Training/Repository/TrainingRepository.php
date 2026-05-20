<?php

declare(strict_types=1);

namespace App\Training\Repository;

use App\Booking\Enum\BookingStatusEnum;
use App\Trainer\Entity\Trainer;
use App\Training\Entity\Training;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Training>
 */
final class TrainingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Training::class);
    }

    public function create(Training $training): void
    {
        $this->getEntityManager()->persist($training);
    }

    public function remove(Training $training): void
    {
        $this->getEntityManager()->remove($training);
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function countByTrainer(Trainer $trainer): int
    {
        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->innerJoin('t.trainerWorkTime', 'wt')
            ->where('wt.trainer = :trainer')
            ->setParameter('trainer', $trainer)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function findScheduledTrainings(Trainer $trainer): int
    {
        $date = new DateTimeImmutable()->setTime(0, 0);

        return (int) $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->innerJoin('t.trainerWorkTime', 'wt')
            ->innerJoin('t.booking', 'b')
            ->where('wt.trainer = :trainer')
            ->andWhere('wt.date >= :date')
            ->andWhere('b.status = :status')
            ->setParameter('trainer', $trainer)
            ->setParameter('date', $date)
            ->setParameter('status', BookingStatusEnum::SCHEDULED)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
