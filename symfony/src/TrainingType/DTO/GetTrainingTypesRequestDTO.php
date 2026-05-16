<?php

declare(strict_types=1);

namespace App\TrainingType\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class GetTrainingTypesRequestDTO
{
    const array ALLOWED_SORT_FIELDS = ['name'];

    public function __construct(
        public string $sort = 'name:ASC',

        #[Assert\Positive]
        public int $page = 1,

        #[Assert\Positive]
        public int $limit = 20,
    )
    {}
}
