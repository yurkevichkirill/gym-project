<?php

declare(strict_types=1);

namespace App\MembershipPlan\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateMembershipPlanRequest
{
    public function __construct(
        public ?string $name,
        #[Assert\Positive]
        public ?int $durationDays,
        #[Assert\Positive]
        public ?int $sessionLimit,
        #[Assert\Positive]
        public ?string $price,
    )
    {}
}
