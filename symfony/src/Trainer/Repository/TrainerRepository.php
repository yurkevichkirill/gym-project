<?php

declare(strict_types=1);

namespace App\Trainer\Repository;

use App\Trainer\Entity\Trainer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
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

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function findForUpdate(int $id): ?Trainer
    {
        return $this->getEntityManager()->find(
            Trainer::class,
            $id,
            LockMode::PESSIMISTIC_WRITE,
        );
    }
}
