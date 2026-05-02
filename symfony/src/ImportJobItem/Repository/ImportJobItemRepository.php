<?php

namespace App\ImportJobItem\Repository;

use App\ImportJobItem\Entity\ImportJobItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ImportJobItem>
 */
class ImportJobItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ImportJobItem::class);
    }
}
