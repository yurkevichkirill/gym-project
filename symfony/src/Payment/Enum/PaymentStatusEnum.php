<?php

declare(strict_types=1);

namespace App\Payment\Enum;

enum PaymentStatusEnum: string
{
    case SUCCEEDED = 'succeeded';
    case PENDING = 'pending';
    case FAILED = 'failed';
    case CANCELED = 'canceled';
}
