<?php

declare(strict_types=1);

namespace App\Membership\DTO;

use App\Client\Entity\Client;
use App\MembershipPlan\Entity\MembershipPlan;

final readonly class MembershipFilter
{
    public function __construct(
        public ?Client $client,
        public ?MembershipPlan $membershipPlan,
        public ?string $status,
        public ?int $minVisits,
        public ?int $maxVisits,
    ) {}
}
