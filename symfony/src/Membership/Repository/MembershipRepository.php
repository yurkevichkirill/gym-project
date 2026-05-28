<?php

declare(strict_types=1);

namespace App\Membership\Repository;

use App\Client\Entity\Client;
use App\Membership\Entity\Membership;
use App\Membership\Enum\MembershipStatusEnum;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Membership>
 */
final class MembershipRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Membership::class);
    }

    public function create(Membership $membership): void
    {
        $this->getEntityManager()->persist($membership);
    }

    public function remove(Membership $membership): void
    {
        $this->getEntityManager()->remove($membership);
    }

    /**
     * @return list<Membership>
     */
    public function findExpired(DateTimeImmutable $curDate): array
    {
        return $this->createQueryBuilder('m')
            ->where("m.status = :active")
            ->andWhere("m.endDate <= :curDate")
            ->setParameter("active", MembershipStatusEnum::ACTIVE)
            ->setParameter("curDate", $curDate)
            ->getQuery()
            ->getResult();
    }

    public function findActive(Client $client): Membership | null
    {
        return $this->findOneBy([
            'client' => $client,
            'status' => MembershipStatusEnum::ACTIVE
        ]);
    }

    public function findBlockingMembership(Client $client): ?Membership
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.client = :client')
            ->andWhere('m.status IN (:statuses)')
            ->setParameter('client', $client)
            ->setParameter('statuses', [
                MembershipStatusEnum::ACTIVE,
                MembershipStatusEnum::FROZEN,
            ])
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
