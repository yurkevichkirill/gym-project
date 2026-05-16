<?php

declare(strict_types=1);

namespace App\Payment\Mapper;

use App\Payment\DTO\PaymentTrainerResponseDTO;
use App\Payment\Entity\Payment;

interface PaymentTrainerMapperInterface
{
    public function map(Payment $payment): PaymentTrainerResponseDTO;
}
