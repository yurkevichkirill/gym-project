<?php

declare(strict_types=1);

namespace App\Membership\DTO;

use App\Client\Entity\Client;
use App\MembershipPlan\Entity\MembershipPlan;

class GetMemberships
{
    public function __construct(
        public array $sort,
        public MembershipFilter $filter,
        public int $page = 1,
        public int $limit = 20,
    ) {}
}
