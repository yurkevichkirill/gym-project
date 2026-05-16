<?php

declare(strict_types=1);

namespace App\Payment\Mapper;

use App\Payment\DTO\PaymentTrainerResponseDTO;
use App\Payment\Entity\Payment;
use App\Payment\Mapper\PaymentTrainerMapperInterface;

final readonly class PaymentTrainerMapper implements PaymentTrainerMapperInterface
{
    public function map(Payment $payment): PaymentTrainerResponseDTO
    {
        return PaymentTrainerResponseDTO::fromEntity($payment);
    }
}
