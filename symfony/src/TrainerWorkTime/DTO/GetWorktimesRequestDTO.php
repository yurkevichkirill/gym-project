<?php

declare(strict_types=1);

namespace App\TrainerWorkTime\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class GetWorktimesRequestDTO
{
    public function __construct(
        #[Assert\Date]
        public ?string $date = null,

        #[Assert\Type('integer')]
        #[Assert\Positive]
        public ?int $trainerId = null,

        public string $sort = 'date:ASC',

        #[Assert\Positive]
        public int $page = 1,

        #[Assert\Positive]
        #[Assert\LessThanOrEqual(100)]
        public int $limit = 20,
    )
    {}
}
