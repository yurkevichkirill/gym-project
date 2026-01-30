<?php

declare(strict_types=1);

namespace App\Payment\Enum;

enum PaymentCategoryEnum: string
{
    case MEMBERSHIP = 'membership';
    case TRAINER = 'trainer';
}
