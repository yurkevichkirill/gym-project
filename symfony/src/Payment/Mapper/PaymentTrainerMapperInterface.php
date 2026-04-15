<?php

declare(strict_types=1);

namespace App\Payment\Mapper;

use App\Payment\DTO\PaymentTrainerResponse;
use App\Payment\Entity\Payment;

interface PaymentTrainerMapperInterface
{
    public function map(Payment $payment): PaymentTrainerResponse;
}
