<?php

declare(strict_types=1);

namespace App\Trainer\DTO;


class GetTrainers
{
    public function __construct(
        public array $sort,
        public TrainerFilter $filter,
        public int $page = 1,
        public int $limit = 20,
    ) {}
}
