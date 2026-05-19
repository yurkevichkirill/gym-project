<?php

declare(strict_types=1);

namespace App\Client\Repository;

use App\Client\Entity\Client;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Client>
 */
final class ClientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Client::class);
    }

    public function create(Client $client): void
    {
        $this->getEntityManager()->persist($client);
    }

    public function remove(Client $client): void
    {
        $this->getEntityManager()->remove($client);
    }
}
