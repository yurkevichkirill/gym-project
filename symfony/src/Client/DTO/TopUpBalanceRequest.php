<?php

declare(strict_types=1);

namespace App\Client\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class TopUpBalanceRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\GreaterThan(100)]
        public int $amount,
    )
    {}
}
