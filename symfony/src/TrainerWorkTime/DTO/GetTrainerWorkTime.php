<?php

declare(strict_types=1);

namespace App\TrainerWorkTime\DTO;

use App\Trainer\Entity\Trainer;

final readonly class GetTrainerWorkTime
{
    public function __construct(
        public array $sort,
        public WorkTimeFilter $filter,
        public int $page = 1,
        public int $limit = 20,
    ) {}
}
