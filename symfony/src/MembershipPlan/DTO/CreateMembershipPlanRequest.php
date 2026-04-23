<?php

declare(strict_types=1);

namespace App\MembershipPlan\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateMembershipPlanRequest
{
    public function __construct(
        #[Assert\NotBlank]
        public string $name,
        #[Assert\NotBlank]
        #[Assert\Positive]
        public int $durationDays,
        #[Assert\Positive]
        public ?int $sessionLimit,
        #[Assert\NotBlank]
        #[Assert\Positive]
        public int $price,
    )
    {}
}
