<?php

declare(strict_types=1);

namespace App\TrainingType\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateTrainingTypeRequestDTO
{
    public function __construct(
        #[Assert\NotBlank]
        public string $name,
        #[Assert\NotBlank]
        public string $description,
        #[Assert\NotBlank]
        public string $photoUrl,
    )
    {}
}
