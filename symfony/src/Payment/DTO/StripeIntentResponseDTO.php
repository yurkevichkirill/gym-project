<?php

declare(strict_types=1);

namespace App\Payment\DTO;

final readonly class StripeIntentResponseDTO
{
    public function __construct(
        public string $clientSecret,
    )
    {}
}
