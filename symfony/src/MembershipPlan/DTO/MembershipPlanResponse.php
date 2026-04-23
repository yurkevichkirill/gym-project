<?php

declare(strict_types=1);

namespace App\MembershipPlan\DTO;

use App\MembershipPlan\Entity\MembershipPlan;

final readonly class MembershipPlanResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public int $durationDays,
        public int $price,
        public ?int $sessionLimit = null,
    )
    {}

    public static function fromEntity(MembershipPlan $membershipPlan): self
    {
        return new self(
            id: $membershipPlan->getId(),
            name: $membershipPlan->getName(),
            durationDays: $membershipPlan->getDurationDays(),
            price: $membershipPlan->getPrice(),
            sessionLimit: $membershipPlan->getSessionLimit(),
        );
    }
}
