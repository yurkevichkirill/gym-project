<?php

declare(strict_types=1);

namespace App\Payment\Enum;

enum PaymentMethodEnum: string
{
    case BALANCE = 'balance';
    case CARD = 'card';
}
