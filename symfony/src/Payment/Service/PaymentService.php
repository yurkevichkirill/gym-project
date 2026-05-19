<?php

declare(strict_types=1);

namespace App\Payment\Service;

use App\Client\Entity\Client;
use App\Payment\Entity\Payment;
use App\Payment\Enum\PaymentCategoryEnum;
use App\Payment\Enum\PaymentMethodEnum;
use App\Payment\Enum\PaymentStatusEnum;
use App\Payment\Repository\PaymentRepository;
use App\Trainer\Entity\Trainer;
use DateTimeImmutable;

final readonly class PaymentService
{
    public function __construct(
        private PaymentRepository $paymentRepo,
    ) {}

    public function createPayment(
        Client $client,
        int $amount,
        PaymentCategoryEnum $category,
        PaymentMethodEnum $method,
        ?Trainer $trainer = null
    ): Payment {
        $payment = new Payment($method);
        $payment->setClient($client);
        $payment->setAmount($amount);
        $payment->setCategory($category);
        $payment->setStatus(PaymentStatusEnum::PENDING);
        $payment->setExpiresAt(new DateTimeImmutable('+5 minutes'));

        if ($trainer) {
            $payment->setTrainer($trainer);
        }

        $this->paymentRepo->create($payment);

        return $payment;
    }
}
