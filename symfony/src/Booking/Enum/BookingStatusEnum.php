<?php

declare(strict_types=1);

namespace App\Booking\Enum;

enum BookingStatusEnum: string
{
    case SCHEDULED = 'scheduled';
    case COMPLETED = 'completed';
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case CANCELED_BY_TRAINER = 'canceled_by_trainer';
    case CANCELED_BY_CLIENT = 'canceled_by_client';
    case CANCELED_BY_SYSTEM = 'canceled_by_system';
    case CANCELED_PAYMENT_FAILED = 'canceled_payment_failed';
}
