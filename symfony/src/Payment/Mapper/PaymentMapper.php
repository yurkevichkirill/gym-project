<?php

declare(strict_types=1);

namespace App\Payment\Mapper;

use App\Payment\DTO\PaymentResponseDTO;
use App\Payment\Entity\Payment;

final class PaymentMapper implements PaymentMapperInterface
{
    public function map(Payment $payment): PaymentResponseDTO
    {
        return PaymentResponseDTO::fromEntity($payment);
    }
}
