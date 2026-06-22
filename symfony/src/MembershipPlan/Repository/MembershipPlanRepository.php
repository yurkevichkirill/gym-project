<?php

declare(strict_types=1);

namespace App\MembershipPlan\Repository;

use App\MembershipPlan\Entity\MembershipPlan;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MembershipPlan>
 */
final class MembershipPlanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MembershipPlan::class);
    }

    public function create(MembershipPlan $membershipPlan): void
    {
        $this->getEntityManager()->persist($membershipPlan);
    }

    public function remove(MembershipPlan $membershipPlan): void
    {
        $this->getEntityManager()->remove($membershipPlan);
    }
}
