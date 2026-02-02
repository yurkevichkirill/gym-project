<?php

declare(strict_types=1);

namespace App\MembershipPlan\Service;

use App\MembershipPlan\Repository\MembershipPlanRepository;
use App\MembershipPlan\Service\MembershipPlanServiceInterface;

readonly class MembershipPlanService implements MembershipPlanServiceInterface
{
    public function __construct(
        private MembershipPlanRepository $membershipPlanRepo
    )
    {
    }

    public function findBy(array $sort, ?int $sessionLimit): array
    {
        $criteria = [];
        if($sessionLimit === 0) {
            $criteria['sessionLimit'] = null;
        } else if($sessionLimit) {
            $criteria['sessionLimit'] = $sessionLimit;
        }

        return $this->membershipPlanRepo->findBy($criteria, $sort);
    }
}
