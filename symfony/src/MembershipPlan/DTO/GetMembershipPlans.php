<?php

declare(strict_types=1);

namespace App\MembershipPlan\DTO;

class GetMembershipPlans
{
    public function __construct(
    public array $sort,
    public MembershipPlanFilter $filter,
    public int $page = 1,
    public int $limit = 20,
) {}
}
