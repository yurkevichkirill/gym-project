<?php

declare(strict_types=1);

namespace App\Payment\Service;

use App\Exception\InvalidPaymentStatusException;
use App\Payment\Entity\Payment;
use App\Payment\Enum\PaymentStatusEnum;
use DateTimeImmutable;

final readonly class PaymentLifecycleService
{
    private const array ALLOWED_TRANSITIONS = [
        PaymentStatusEnum::PENDING->value => [
            PaymentStatusEnum::SUCCEEDED,
            PaymentStatusEnum::CANCELED,
            PaymentStatusEnum::FAILED,
        ],
        PaymentStatusEnum::SUCCEEDED->value => [],
        PaymentStatusEnum::FAILED->value => [],
        PaymentStatusEnum::CANCELED->value => [],
    ];

    public function transitionTo(Payment $payment, PaymentStatusEnum $newStatus): void
    {
        $currentStatus = $payment->getStatus();

        $allowedStatuses = self::ALLOWED_TRANSITIONS[$currentStatus->value] ?? [];

        if (!in_array($newStatus, $allowedStatuses, true)) {
            throw new InvalidPaymentStatusException(sprintf(
                'Invalid payment status transition: %s -> %s',
                $currentStatus->value,
                $newStatus->value
            ));
        }

        $payment->setStatus($newStatus);

        switch ($newStatus) {
            case PaymentStatusEnum::SUCCEEDED:
                $payment->setPaidAt(new DateTimeImmutable());
                $payment->setExpiresAt(null);
                break;

            case PaymentStatusEnum::CANCELED:
            case PaymentStatusEnum::FAILED:
                $payment->setExpiresAt(null);
                break;

            case PaymentStatusEnum::PENDING:
                throw new InvalidPaymentStatusException("Payment status cannot transit to pending");
        }
    }
}
