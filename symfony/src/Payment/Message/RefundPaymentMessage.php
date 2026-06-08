<?php

declare(strict_types=1);

namespace App\Payment\Message;

final readonly class RefundPaymentMessage
{
    public function __construct(
        public int $paymentId,
        public string $intentId,
    ) {}
}
