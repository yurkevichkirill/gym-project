<?php

declare(strict_types=1);

namespace App\Payment\DTO;

use App\Payment\Entity\Payment;
use App\Payment\Enum\PaymentCategoryEnum;
use App\Payment\Enum\PaymentStatusEnum;

final readonly class PaymentResponse
{
    public function __construct(
        public int $id,
        public ?int $trainerId = null,
        public string $amount,
        public PaymentCategoryEnum $category
    )
    {}

    public static function fromEntity(Payment $payment): self
    {
        return new self(
            id: $payment->getId(),
            trainerId: $payment->getTrainer()?->getId(),
            amount: $payment->getAmount(),
            category: $payment->getCategory()
        );
    }
}
