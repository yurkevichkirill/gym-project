<?php

declare(strict_types=1);

namespace App\Contact\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class ContactRequestDTO
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 100)]
        public string $name,

        #[Assert\NotBlank]
        #[Assert\Email]
        #[Assert\Length(max: 254)]
        public string $email,

        #[Assert\NotBlank]
        #[Assert\Length(max: 2000)]
        public string $message,
    ) {}
}
