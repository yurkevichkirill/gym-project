<?php

declare(strict_types=1);

namespace App\Enum;

enum PaymentStatusEnum: string
{
    case PAID = 'paid';
    case PENDING = 'pending';
    case CANCELLED = 'cancelled';
}
