<?php

declare(strict_types=1);

namespace App\MembershipPlan\Service;

interface MembershipPlanServiceInterface
{
    public function findBy(array $sort, ?int $sessionLimit): array;
}
