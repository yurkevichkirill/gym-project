<?php

declare(strict_types=1);

namespace App\Payment\Service;

use App\Payment\Entity\Payment;
use App\Payment\Enum\PaymentStatusEnum;
use App\Payment\Exception\InvalidPaymentStatusException;
use DateTimeImmutable;

final readonly class PaymentLifecycleService
{
    private const array ALLOWED_TRANSITIONS = [
        PaymentStatusEnum::PENDING->value => [
            PaymentStatusEnum::SUCCEEDED,
            PaymentStatusEnum::CANCELED,
            PaymentStatusEnum::FAILED,
        ],
        PaymentStatusEnum::SUCCEEDED->value => [
            PaymentStatusEnum::REFUND_PENDING,
            PaymentStatusEnum::REFUNDED,
        ],
        PaymentStatusEnum::REFUND_PENDING->value => [
            PaymentStatusEnum::REFUNDED,
            PaymentStatusEnum::REFUND_FAILED,
        ],
        PaymentStatusEnum::REFUND_FAILED->value => [
            PaymentStatusEnum::REFUND_PENDING,
            PaymentStatusEnum::REFUNDED,
        ],
        PaymentStatusEnum::FAILED->value => [
            PaymentStatusEnum::REFUND_PENDING,
            PaymentStatusEnum::REFUNDED,
        ],
        PaymentStatusEnum::CANCELED->value => [
            PaymentStatusEnum::REFUND_PENDING,
            PaymentStatusEnum::REFUNDED,
        ],
        PaymentStatusEnum::REFUNDED->value => [],
    ];

    /**
     * @throws InvalidPaymentStatusException
     */
    public function transitionTo(Payment $payment, PaymentStatusEnum $newStatus): void
    {
        $currentStatus = $payment->getStatus();

        $allowedStatuses = self::ALLOWED_TRANSITIONS[$currentStatus->value];

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

            case PaymentStatusEnum::REFUND_PENDING:
            case PaymentStatusEnum::REFUNDED:
            case PaymentStatusEnum::REFUND_FAILED:
                break;
        }
    }
}
