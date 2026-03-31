<?php

declare(strict_types=1);

namespace App\Membership\DTO;

final readonly class CreateMembershipRequest
{
    public function __construct(
        public int $membershipPlanId
    )
    {}
}
