<?php

declare(strict_types=1);

namespace App\Client\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ClientActivateRequestDTO
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(exactly: 64)]
        #[Assert\Regex(pattern: '/^[a-f0-9]{64}$/i')]
        public string $activationToken,
        #[Assert\PasswordStrength(
            minScore: Assert\PasswordStrength::STRENGTH_MEDIUM,
        )]
        public string $password,
    )
    {}
}
