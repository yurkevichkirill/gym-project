<?php

declare(strict_types=1);

namespace App\Trainer\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class GetTrainersRequestAdminDTO
{
    public function __construct(
        #[Assert\Positive]
        public ?int $minPricePerHour = null,

        #[Assert\Positive]
        public ?int $maxPricePerHour = null,

        #[Assert\Type('integer')]
        #[Assert\Positive]
        public ?int $trainingTypeId = null,

        #[Assert\GreaterThanOrEqual(0)]
        public ?int $minBalance = null,

        #[Assert\GreaterThanOrEqual(0)]
        public ?int $maxBalance = null,

        public ?bool $isDeleted = null,

        public ?bool $isBlocked = null,

        public string $sort = 'lastName:ASC',

        #[Assert\Positive]
        public int $page = 1,

        #[Assert\Positive]
        public int $limit = 20,
    )
    {}
}
