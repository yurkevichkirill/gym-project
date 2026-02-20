<?php

declare(strict_types=1);

namespace App\Payment\Mapper;

use App\Payment\DTO\PaymentResponse;
use App\Payment\Entity\Payment;

interface PaymentMapperInterface
{
    public function map(Payment $payment): PaymentResponse;
}
