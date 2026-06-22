<?php

declare(strict_types=1);

namespace App\Membership\Repository;

use App\Client\Entity\Client;
use App\Membership\Entity\Membership;
use App\Membership\Enum\MembershipStatusEnum;
use App\MembershipPlan\Entity\MembershipPlan;
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

    public function recordVisit(Client $client, DateTimeImmutable $now): ?Membership
    {
        $clientId = $client->getId();
        if ($clientId === null) {
            return null;
        }

        $membershipId = $this->getEntityManager()->getConnection()
            ->executeQuery(
                <<<'SQL'
                    UPDATE membership
                    SET
                        visits = visits + 1,
                        status = CASE
                            WHEN session_limit IS NOT NULL AND visits + 1 >= session_limit THEN :expiredStatus
                            ELSE status
                        END
                    WHERE client_id = :clientId
                      AND status = :activeStatus
                      AND (end_date IS NULL OR end_date >= :now)
                      AND (session_limit IS NULL OR visits < session_limit)
                    RETURNING id
                SQL,
                [
                    'clientId' => $clientId,
                    'activeStatus' => MembershipStatusEnum::ACTIVE->value,
                    'expiredStatus' => MembershipStatusEnum::EXPIRED->value,
                    'now' => $now,
                ],
                [
                    'now' => 'datetime_immutable',
                ]
            )
            ->fetchOne();

        if (!is_int($membershipId) && !is_string($membershipId)) {
            return null;
        }

        $membership = $this->find((int) $membershipId);
        if (!$membership instanceof Membership) {
            return null;
        }

        $this->getEntityManager()->refresh($membership);

        return $membership;
    }

    public function findBlockingMembership(Client $client): ?Membership
    {
        $membership = $this->createQueryBuilder('m')
            ->andWhere('m.client = :client')
            ->andWhere('m.status IN (:statuses)')
            ->setParameter('client', $client)
            ->setParameter('statuses', [
                MembershipStatusEnum::ACTIVE,
                MembershipStatusEnum::FROZEN,
                MembershipStatusEnum::PENDING,
            ])
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $membership instanceof Membership ? $membership : null;
    }

    public function existsForPlan(MembershipPlan $plan): bool
    {
        return $this->createQueryBuilder('m')
            ->select('1')
            ->andWhere('m.plan = :plan')
            ->setParameter('plan', $plan)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult() !== null;
    }
}
