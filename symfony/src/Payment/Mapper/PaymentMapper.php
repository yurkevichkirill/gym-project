<?php

declare(strict_types=1);

namespace App\Payment\Mapper;

use App\Payment\DTO\PaymentResponse;
use App\Payment\Entity\Payment;
use App\Payment\Mapper\PaymentMapperInterface;

class PaymentMapper implements PaymentMapperInterface
{
    public function map(Payment $payment): PaymentResponse
    {
        return PaymentResponse::fromEntity($payment);
    }
}
