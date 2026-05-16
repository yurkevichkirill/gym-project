<?php

declare(strict_types=1);

namespace App\Trainer\DTO;

use App\TrainingType\Entity\TrainingType;

final readonly class ResolvedTrainersRequestAdminDTO
{
    public const array ALLOWED_SORT_FIELDS = ['pricePerHour', 'firstName', 'lastName', 'trainingTypeId', 'balance'];

    public function __construct(
        public ?int $minPricePerHour = null,
        public ?int $maxPricePerHour = null,
        public ?TrainingType $trainingType = null,
        public ?int $minBalance = null,
        public ?int $maxBalance = null,
        public ?bool $isDeleted = null,
        public ?bool $isBlocked = null,
        public string $sort = 'lastName:ASC',
        public int $page = 1,
        public int $limit = 20,
    )
    {}
}
