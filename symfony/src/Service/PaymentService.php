<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Client;
use App\Entity\Payment;
use App\Enum\PaymentStatusEnum;

class PaymentService implements PaymentServiceInterface
{
    public function pay(Client $client, Payment $payment): void
    {
        if($payment->getStatus() === PaymentStatusEnum::PAID) {
            return;
        }
        $newBalance = $client->getBalance() - $payment->getAmount();
        $client->setBalance((string) $newBalance);
        $payment->setStatus(PaymentStatusEnum::PAID);
    }

    public function cancel(Payment $payment): void
    {
        $payment->setStatus(PaymentStatusEnum::CANCELLED);
    }
}
