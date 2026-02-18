<?php

declare(strict_types=1);

namespace App\TrainerWorkTime\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateWorkTimeRequest
{
    public function __construct(
        #[Assert\Time]
        public ?string $startTime = null,
        #[Assert\Time]
        public ?string $endTime = null,
    )
    {}
}
