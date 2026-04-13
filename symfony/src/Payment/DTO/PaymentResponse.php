<?php

declare(strict_types=1);

namespace App\Payment\DTO;

use App\Payment\Entity\Payment;
use App\Payment\Enum\PaymentCategoryEnum;
use App\Trainer\DTO\TrainerResponse;

final readonly class PaymentResponse
{
    public function __construct(
        public int $id,
        public ?TrainerResponse $trainer = null,
        public string $amount,
        public PaymentCategoryEnum $category,
        public bool $isRefund,
    )
    {}

    public static function fromEntity(Payment $payment): self
    {
        return new self(
            id: $payment->getId(),
            trainer: $payment->getTrainer() ? TrainerResponse::fromEntity($payment->getTrainer()) : null,
            amount: (string) $payment->getAmount(),
            category: $payment->getCategory(),
            isRefund: $payment->getIsRefund(),
        );
    }
}
