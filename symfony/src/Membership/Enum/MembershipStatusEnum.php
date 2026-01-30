<?php

declare(strict_types=1);

namespace App\Membership\Enum;

enum MembershipStatusEnum: string
{
    case ACTIVE = 'active';
    case EXPIRED = 'expired';
    case FROZEN = 'frozen';
}
