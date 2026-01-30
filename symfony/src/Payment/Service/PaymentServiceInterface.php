<?php

declare(strict_types=1);

namespace App\Payment\Service;

use App\Client\Entity\Client;
use App\Payment\Entity\Payment;

interface PaymentServiceInterface
{
    public function pay(Client $client, Payment $payment): void;
    public function cancel(Payment $payment): void;
}
