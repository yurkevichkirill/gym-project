<?php

declare(strict_types=1);

namespace App\TrainerWorkTime\DTO;

use App\Trainer\Entity\Trainer;
use DateTimeImmutable;

final readonly class ResolvedWorktimesRequestDTO
{
    const array ALLOWED_SORT_FIELDS = ['date', 'startTime', 'endTime'];
    public function __construct(
        public ?DateTimeImmutable $date = null,
        public ?Trainer $trainer = null,
        public string $sort = 'date:ASC',
        public int $page = 1,
        public int $limit = 20,
    )
    {}
}
