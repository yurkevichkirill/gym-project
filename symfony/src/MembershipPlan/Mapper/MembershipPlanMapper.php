<?php

declare(strict_types=1);

namespace App\MembershipPlan\Mapper;

use App\MembershipPlan\DTO\MembershipPlanResponseDTO;
use App\MembershipPlan\Entity\MembershipPlan;

class MembershipPlanMapper implements MembershipPlanMapperInterface
{

    public function map(MembershipPlan $plan): MembershipPlanResponseDTO
    {
        return MembershipPlanResponseDTO::fromEntity($plan);
    }
}
