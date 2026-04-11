<?php

declare(strict_types=1);

namespace App\Payment\Service;

use App\Client\Entity\Client;
use App\Client\Service\AvailabilityService;
use App\Exception\InsufficientFundsException;
use App\Payment\Entity\Payment;
use App\Payment\Enum\PaymentCategoryEnum;
use App\Payment\Repository\PaymentRepository;
use App\Trainer\Entity\Trainer;

final readonly class PaymentService
{
    public function __construct(
        private PaymentRepository $paymentRepo,
        private AvailabilityService $availabilityService,
    )
    {}

    public function pay(Client $client, float $price, ?Trainer $trainer = null): Payment
    {
        $clientBalance = (float) $client->getBalance();
        if (!$this->availabilityService->hasClientEnoughMoney($clientBalance, $price)) {
            throw new InsufficientFundsException();
        }

        $payment = new Payment();
        $payment->setClient($client);
        $payment->setAmount((string) $price);
        $payment->setIsRefund(false);
        if ($trainer) {
            $payment->setTrainer($trainer);
            $payment->setCategory(PaymentCategoryEnum::TRAINER);

            $trainerBalance = $trainer->getBalance();
            $trainer->setBalance((string) ($trainerBalance + $price));
        } else {
            $payment->setCategory(PaymentCategoryEnum::MEMBERSHIP);
        }
        $this->paymentRepo->create($payment);

        $client->setBalance((string) ($clientBalance - $price));

        return $payment;
    }

    public function refund(Client $client, Payment $payment): void
    {
        $clientBalance = (float) $client->getBalance();

        $paymentRefund = new Payment();
        $paymentRefund->setClient($client);
        $paymentRefund->setAmount($payment->getAmount());
        $paymentRefund->setIsRefund(true);

        $trainer = $payment->getTrainer();
        if ($trainer !== null) {
            $paymentRefund->setTrainer($payment->getTrainer());

            $trainerBalance = $trainer->getBalance();
            $trainer->setBalance((string) ($trainerBalance - $payment->getAmount()));
        }
        $paymentRefund->setCategory($payment->getCategory());

        $this->paymentRepo->create($paymentRefund);

        $client->setBalance((string) ($clientBalance + $payment->getAmount()));
    }
}
