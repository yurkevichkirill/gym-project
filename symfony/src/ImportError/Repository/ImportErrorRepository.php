<?php

declare(strict_types=1);

namespace App\ImportError\Repository;

use App\ImportError\Entity\ImportError;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ImportError>
 */
class ImportErrorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ImportError::class);
    }
}
