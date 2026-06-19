<?php

declare(strict_types=1);

namespace App\Payment\Enum;

enum PaymentStatusEnum: string
{
    case SUCCEEDED = 'succeeded';
    case PENDING = 'pending';
    case FAILED = 'failed';
    case CANCELED = 'canceled';
    case REFUND_PENDING = 'refund_pending';
    case REFUNDED = 'refunded';
    case REFUND_FAILED = 'refund_failed';
}
