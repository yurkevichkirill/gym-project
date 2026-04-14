<?php

declare(strict_types=1);

namespace App\Membership\Enum;

enum MembershipStatusEnum: string
{
    case ACTIVE = 'active';
    case EXPIRED = 'expired';
    case FROZEN = 'frozen';
    case PENDING = 'pending';
    case CANCELED_PAYMENT_FAILED = 'canceled_payment_failed';
    case CANCELED_BY_CLIENT = 'canceled_by_client';
    case CANCELED_BY_SYSTEM = 'canceled_by_system';
}
