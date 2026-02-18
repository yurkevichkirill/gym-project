<?php

declare(strict_types=1);

namespace App\Training\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class TrainingRequest
{
    public function __construct(
        #[Assert\Time]
        public ?string $startTime = null,
        public ?int $durationMinutes = null,
        #[Assert\Date]
        public ?string $date = null,
    )
    {}
}
