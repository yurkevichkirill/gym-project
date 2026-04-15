<?php

declare(strict_types=1);

namespace App\TrainerWorkTime\DTO;

use App\Trainer\Entity\Trainer;
use DateTimeImmutable;

final readonly class WorkTimeFilter
{
    public function __construct(
        public ?Trainer           $trainer,
        public ?DateTimeImmutable $date,
    ) {}
}
