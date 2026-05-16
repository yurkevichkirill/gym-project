<?php

declare(strict_types=1);

namespace App\Membership\DTO;

use App\Membership\Enum\MembershipStatusEnum;

final readonly class UpdateMembershipRequestDTO
{
    public function __construct(
        public ?MembershipStatusEnum $status,
    )
    {}
}
