<?php

declare(strict_types=1);

namespace App\Payment\DTO;

use App\Payment\Entity\Payment;
use App\Payment\Enum\PaymentCategoryEnum;
use App\Payment\Enum\PaymentMethodEnum;
use App\Payment\Enum\PaymentStatusEnum;
use App\Trainer\DTO\TrainerResponseDTO;
use LogicException;

final readonly class PaymentResponseDTO
{
    public function __construct(
        public int $id,
        public int                 $amount,
        public string              $currency,
        public PaymentMethodEnum   $method,
        public PaymentCategoryEnum $category,
        public ?string             $stripePaymentIntentId,
        public PaymentStatusEnum   $status,
        public bool                $isRefund,
        public string              $createdAt,
        public ?string             $paidAt,
        public ?string             $expiresAt,
        public ?TrainerResponseDTO $trainer = null,
        public ?Payment            $originalPayment = null,
    )
    {}

    public static function fromEntity(Payment $payment): self
    {
        $id = $payment->getId();
        $createdAt = $payment->getCreatedAt();

        if ($id === null || $createdAt === null) {
            throw new LogicException('Payment is not fully initialized.');
        }

        return new self(
            id: $id,
            amount: $payment->getAmount(),
            currency: $payment->getCurrency(),
            method: $payment->getMethod(),
            category: $payment->getCategory(),
            stripePaymentIntentId: $payment->getStripePaymentIntentId(),
            status: $payment->getStatus(),
            isRefund: $payment->getIsRefund(),
            createdAt: $createdAt->format(DATE_ATOM),
            paidAt: $payment->getPaidAt()?->format(DATE_ATOM) ?? '',
            expiresAt: $payment->getExpiresAt()?->format(DATE_ATOM) ?? '',
            trainer: $payment->getTrainer() !== null ? TrainerResponseDTO::fromEntity($payment->getTrainer()) : null,
            originalPayment: $payment->getOriginalPayment() !== null ? PaymentResponseDTO::fromEntity($payment->getOriginalPayment()) : null,
        );
    }
}
