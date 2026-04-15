<?php

declare(strict_types=1);

namespace App\Training\DTO;

use App\Client\Entity\Client;
use App\Trainer\Entity\Trainer;

final readonly class GetTrainings
{
    public function __construct(
        public array $sort,
        public TrainingFilter $filter,
        public int $page = 1,
        public int $limit = 20,
    ) {}
}
