<?php

declare(strict_types=1);

namespace App\Membership\DTO;

final readonly class CreateMembershipRequestDTO
{
    public function __construct(
        public int $membershipPlanId
    )
    {}
}
