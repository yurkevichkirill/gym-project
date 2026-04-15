<?php

declare(strict_types=1);

namespace App\MembershipPlan\DTO;

final readonly class MembershipPlanFilter
{
    public function __construct(
        public ?int $minPrice = null,
        public ?int $maxPrice = null,
        public ?int $minDurationDays = null,
        public ?int $maxDurationDays = null,
        public ?int $minSessionLimit = null,
        public ?int $maxSessionLimit = null,
        public ?bool $isUnlimited = null,
    ) {}
}
