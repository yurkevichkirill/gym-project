<?php

declare(strict_types=1);

namespace App\Enum;

enum PaymentCategoryEnum: string
{
    case MEMBERSHIP = 'membership';
    case TRAINER = 'trainer';
}
