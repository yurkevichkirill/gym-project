<?php

declare(strict_types=1);

namespace App\MembershipPlan\DTO;

use App\MembershipPlan\Entity\MembershipPlan;
use LogicException;

final readonly class MembershipPlanResponseDTO
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
        $id = $membershipPlan->getId();
        if ($id === null) {
            throw new LogicException('Membership plan is not persisted.');
        }

        return new self(
            id: $id,
            name: $membershipPlan->getName(),
            durationDays: $membershipPlan->getDurationDays(),
            price: $membershipPlan->getPrice(),
            sessionLimit: $membershipPlan->getSessionLimit(),
        );
    }
}
