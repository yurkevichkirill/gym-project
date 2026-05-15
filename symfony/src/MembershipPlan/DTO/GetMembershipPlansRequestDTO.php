<?php

declare(strict_types=1);

namespace App\MembershipPlan\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class GetMembershipPlansRequestDTO
{
    public const array ALLOWED_SORT_FIELDS = ['durationDays', 'price', 'sessionLimit'];
    public function __construct(
        #[Assert\Type('integer')]
        #[Assert\Positive]
        public ?int $minPrice = null,

        #[Assert\Type('integer')]
        #[Assert\Positive]
        public ?int $maxPrice = null,

        #[Assert\Type('integer')]
        #[Assert\Positive]
        public ?int $minDurationDays = null,

        #[Assert\Type('integer')]
        #[Assert\Positive]
        public ?int $maxDurationDays = null,

        #[Assert\Type('integer')]
        #[Assert\Positive]
        public ?int $minSessionLimit = null,

        #[Assert\Type('integer')]
        #[Assert\Positive]
        public ?int $maxSessionLimit = null,

        public ?bool $isUnlimited = null,

        public string $sort = 'durationDays:ASC',

        public int $page = 1,

        public int $limit = 20,
    )
    {}
}
