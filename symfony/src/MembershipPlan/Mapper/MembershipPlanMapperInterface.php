<?php

declare(strict_types=1);

namespace App\MembershipPlan\Mapper;

use App\MembershipPlan\DTO\MembershipPlanResponseDTO;
use App\MembershipPlan\Entity\MembershipPlan;

interface MembershipPlanMapperInterface
{
    public function map(MembershipPlan $plan): MembershipPlanResponseDTO;
}
