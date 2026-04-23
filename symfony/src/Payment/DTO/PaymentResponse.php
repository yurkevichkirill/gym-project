<?php

declare(strict_types=1);

namespace App\Payment\DTO;

use App\Payment\Entity\Payment;
use App\Payment\Enum\PaymentCategoryEnum;
use App\Payment\Enum\PaymentStatusEnum;
use App\Trainer\DTO\TrainerResponse;

final readonly class PaymentResponse
{
    public function __construct(
        public int $id,
        public int $amount,
        public string $currency,
        public PaymentCategoryEnum $category,
        public bool $isRefund,
        public ?string $stripePaymentIntentId,
        public ?PaymentStatusEnum $status,
        public string $createdAt,
        public ?string $paidAt,
        public ?string $confirmedAt,
        public ?string $expiresAt,
        public ?TrainerResponse $trainer = null,
    )
    {}

    public static function fromEntity(Payment $payment): self
    {
        return new self(
            id: $payment->getId(),
            amount: $payment->getAmount(),
            currency: $payment->getCurrency(),
            category: $payment->getCategory(),
            isRefund: $payment->getIsRefund(),
            stripePaymentIntentId: $payment->getStripePaymentIntentId(),
            status: $payment->getStatus(),
            createdAt: $payment->getCreatedAt()->format(DATE_ATOM),
            paidAt: $payment->getPaidAt()?->format(DATE_ATOM) ?? '',
            confirmedAt: $payment->getConfirmedAt()?->format(DATE_ATOM) ?? '',
            expiresAt: $payment->getExpiresAt()?->format(DATE_ATOM) ?? '',
            trainer: $payment->getTrainer() ? TrainerResponse::fromEntity($payment->getTrainer()) : null,
        );
    }
}
