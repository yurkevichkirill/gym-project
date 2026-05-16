<?php

declare(strict_types=1);

namespace App\Trainer\DTO;

use App\TrainingType\Entity\TrainingType;

final readonly class ResolvedTrainersRequestDTO
{
    public const array ALLOWED_SORT_FIELDS = ['pricePerHour', 'firstName', 'lastName', 'trainingTypeId'];

    public function __construct(
        public ?int $minPricePerHour = null,
        public ?int $maxPricePerHour = null,
        public ?TrainingType $trainingType = null,
        public string $sort = 'lastName:ASC',
        public int $page = 1,
        public int $limit = 20,
    )
    {}
}
