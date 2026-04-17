<?php

namespace App\ImportJob\Repository;

use App\ImportJob\Entity\ImportJob;
use App\ImportJob\Enum\ImportStatusEnum;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ImportJob>
 */
class ImportJobRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ImportJob::class);
    }
    public function incrementProcessed(int $jobId): void
    {
        $this->createQueryBuilder('j')
            ->update()
            ->set('j.processed', 'j.processed + 1')
            ->where('j.id = :id')
            ->setParameter('id', $jobId)
            ->getQuery()
            ->execute();
    }

    public function incrementFailed(int $jobId): void
    {
        $this->createQueryBuilder('j')
            ->update()
            ->set('j.failed', 'j.failed + 1')
            ->where('j.id = :id')
            ->setParameter('id', $jobId)
            ->getQuery()
            ->execute();
    }

    public function incrementSkipped(int $jobId): void
    {
        $this->createQueryBuilder('j')
            ->update()
            ->set('j.skipped', 'j.skipped + 1')
            ->where('j.id = :id')
            ->setParameter('id', $jobId)
            ->getQuery()
            ->execute();
    }

    public function markFinishedIfDone(int $jobId): void
    {
        $this->createQueryBuilder('j')
            ->update()
            ->set('j.finishedAt', ':now')
            ->set('j.status', ':done')
            ->where('j.id = :id')
            ->andWhere('(j.processed + j.failed + j.skipped) >= j.total')
            ->andWhere('j.failed = 0')
            ->setParameter('id', $jobId)
            ->setParameter('now', new DateTimeImmutable())
            ->setParameter('done', ImportStatusEnum::DONE)
            ->getQuery()
            ->execute();

        $this->createQueryBuilder('j')
            ->update()
            ->set('j.finishedAt', ':now')
            ->set('j.status', ':failed')
            ->where('j.id = :id')
            ->andWhere('(j.processed + j.failed) >= j.total')
            ->andWhere('j.failed > 0')
            ->setParameter('id', $jobId)
            ->setParameter('now', new DateTimeImmutable())
            ->setParameter('failed', ImportStatusEnum::FAILED)
            ->getQuery()
            ->execute();
    }

    public function markProcessing(int $jobId): void
    {
        $this->createQueryBuilder('j')
            ->update()
            ->set('j.status', ':status')
            ->where('j.id = :id')
            ->andWhere('j.status = :pending')
            ->setParameter('id', $jobId)
            ->setParameter('status', ImportStatusEnum::PROCESSING)
            ->setParameter('pending', ImportStatusEnum::PENDING)
            ->getQuery()
            ->execute();
    }
}
