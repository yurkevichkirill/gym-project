<?php

declare(strict_types=1);

namespace App\TrainerWorkTime\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateWorkTimeRequest
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Time]
        public string $startTime,
        #[Assert\NotBlank]
        #[Assert\Time]
        public string $endTime,
        #[Assert\NotBlank]
        #[Assert\Date]
        public string $date,
    )
    {}
}
