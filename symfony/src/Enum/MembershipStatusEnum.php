<?php

declare(strict_types=1);

namespace App\Enum;

enum MembershipStatusEnum: string
{
    case ACTIVE = 'active';
    case EXPIRED = 'expired';
    case FROZEN = 'frozen';
}
