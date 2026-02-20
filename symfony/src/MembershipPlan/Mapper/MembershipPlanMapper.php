<?php

declare(strict_types=1);

namespace App\MembershipPlan\Mapper;

use App\MembershipPlan\DTO\MembershipPlanResponse;
use App\MembershipPlan\Entity\MembershipPlan;
use App\MembershipPlan\Mapper\MembershipPlanMapperInterface;

class MembershipPlanMapper implements MembershipPlanMapperInterface
{

    public function map(MembershipPlan $plan): MembershipPlanResponse
    {
        return MembershipPlanResponse::fromEntity($plan);
    }
}
