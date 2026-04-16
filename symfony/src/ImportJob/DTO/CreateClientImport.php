<?php

declare(strict_types=1);

namespace App\ImportJob\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateClientImport
{
    public function __construct(
        #[Assert\NotBlank(groups: ['import'])]
        #[Assert\Email(groups: ['import'])]
        public ?string $email = null,
        #[Assert\NotBlank(groups: ['import'])]
        public ?string $firstName = null,
        #[Assert\NotBlank(groups: ['import'])]
        public ?string $lastName = null,
        #[Assert\NotBlank(groups: ['import'])]
        public ?string $phone = null,
        #[Assert\NotBlank(groups: ['import'])]
        #[Assert\Positive(groups: ['import'])]
        public ?int $age = null,
    )
    {}
}
