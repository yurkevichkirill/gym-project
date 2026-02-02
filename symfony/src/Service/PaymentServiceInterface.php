<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Client;
use App\Entity\Payment;

interface PaymentServiceInterface
{
    public function pay(Client $client, Payment $payment): void;
    public function cancel(Payment $payment): void;
}
