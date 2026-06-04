<?php

declare(strict_types=1);

namespace App\Client\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateClientRequestDTO
{
    public function __construct(
        #[Assert\Regex(
            pattern: '/^\+?[1-9]\d{4,14}$/'
        )]
        public ?string $phone = null
    )
    {}
}
