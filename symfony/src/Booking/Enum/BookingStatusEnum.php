<?php

declare(strict_types=1);

namespace App\Booking\Enum;

enum BookingStatusEnum: string
{
    case SCHEDULED = 'scheduled';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}
