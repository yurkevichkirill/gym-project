<?php

declare(strict_types=1);

namespace App\Payment\DTO;

use App\Payment\Entity\Payment;
use App\Payment\Enum\PaymentCategoryEnum;
use App\Payment\Enum\PaymentMethodEnum;
use App\Payment\Enum\PaymentStatusEnum;
use App\Trainer\DTO\TrainerResponseDTO;

final readonly class PaymentResponseDTO
{
    public function __construct(
        public int $id,
        public int                 $amount,
        public string              $currency,
        public PaymentMethodEnum   $method,
        public PaymentCategoryEnum $category,
        public ?string             $stripePaymentIntentId,
        public ?PaymentStatusEnum  $status,
        public bool                $isRefund,
        public string              $createdAt,
        public ?string             $paidAt,
        public ?string             $expiresAt,
        public ?TrainerResponseDTO $trainer = null,
    )
    {}

    public static function fromEntity(Payment $payment): self
    {
        return new self(
            id: $payment->getId(),
            amount: $payment->getAmount(),
            currency: $payment->getCurrency(),
            method: $payment->getMethod(),
            category: $payment->getCategory(),
            stripePaymentIntentId: $payment->getStripePaymentIntentId(),
            status: $payment->getStatus(),
            isRefund: $payment->getIsRefund(),
            createdAt: $payment->getCreatedAt()->format(DATE_ATOM),
            paidAt: $payment->getPaidAt()?->format(DATE_ATOM) ?? '',
            expiresAt: $payment->getExpiresAt()?->format(DATE_ATOM) ?? '',
            trainer: $payment->getTrainer() ? TrainerResponseDTO::fromEntity($payment->getTrainer()) : null,
        );
    }
}
