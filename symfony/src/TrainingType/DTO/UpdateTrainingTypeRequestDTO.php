<?php

declare(strict_types=1);

namespace App\TrainingType\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateTrainingTypeRequestDTO
{
    public function __construct(
        public ?string $name,
        public ?string $description,
        public ?string $photoUrl,
    )
    {}
}
