<?php

declare(strict_types=1);

namespace App\MembershipPlan\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class GetMembershipPlansRequestDTO
{
    const array ALLOWED_SORT_FIELDS = ['durationDays', 'price', 'sessionLimit'];

    public function __construct(
        public ?string $name = null,

        #[Assert\GreaterThanOrEqual(0)]
        public ?int $minDurationDays = null,

        #[Assert\GreaterThanOrEqual(0)]
        public ?int $maxDurationDays = null,

        #[Assert\GreaterThanOrEqual(0)]
        public ?int $minSessionLimit = null,

        #[Assert\GreaterThanOrEqual(0)]
        public ?int $maxSessionLimit = null,

        #[Assert\GreaterThanOrEqual(0)]
        public ?int $minPrice = null,

        #[Assert\GreaterThanOrEqual(0)]
        public ?int $maxPrice = null,

        public ?bool $isUnlimited = null,

        public string $sort = 'bookedAt:ASC',

        #[Assert\Positive]
        public int $page = 1,

        #[Assert\Positive]
        public int $limit = 20,
    )
    {}
}
