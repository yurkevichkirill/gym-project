<?php

declare(strict_types=1);

namespace App\Payment\Enum;

enum PaymentStatusEnum: string
{
    case PAID = 'paid';
    case PENDING = 'pending';
    case CANCELLED = 'cancelled';
}
