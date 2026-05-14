<?php

declare(strict_types=1);

namespace App\Membership\DTO;

use App\Client\Entity\Client;
use App\Membership\Enum\MembershipStatusEnum;
use App\MembershipPlan\Entity\MembershipPlan;

final readonly class ResolvedMembershipsRequestDTO
{
    public const array ALLOWED_SORT_FIELDS = ['startDate', 'endDate', 'status', 'visits', 'membershipPlanId'];
    public function __construct(
        public ?MembershipPlan $membershipPlan = null,
        public ?Client $client = null,
        public ?MembershipStatusEnum $status = null,
        public ?int $minVisits = null,
        public ?int $maxVisits = null,
        public string $sort,
        public int $page = 1,
        public int $limit = 20,
    ) {}
}
