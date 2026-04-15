<?php

declare(strict_types=1);

namespace App\TrainingType\DTO;

final readonly class GetTrainingTypes
{
    public function __construct(
        public array $sort,
        public int $page = 1,
        public int $limit = 20,
    ) {}
}
