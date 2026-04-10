<?php

declare(strict_types=1);

namespace App\Membership\DTO;

use App\Membership\Entity\Membership;
use App\Membership\Enum\MembershipStatusEnum;
use App\MembershipPlan\DTO\MembershipPlanResponse;
use App\Payment\DTO\PaymentResponse;

final readonly class MembershipResponse
{
    public function __construct(
        public int $id,
        public MembershipPlanResponse $membershipPlan,
        public string $startDate,
        public string $endDate,
        public MembershipStatusEnum $status,
        public int $visits,
        public string $createdAt,
        public ?string $frozenAt,
        public PaymentResponse $payment,
    )
    {}

    public static function fromEntity(Membership $membership): self
    {
        return new self(
            id: $membership->getId(),
            membershipPlan: MembershipPlanResponse::fromEntity($membership->getPlan()),
            startDate: $membership->getStartDate()->format("Y-m-d"),
            endDate: $membership->getEndDate()->format("Y-m-d"),
            status: $membership->getStatus(),
            visits: $membership->getVisits(),
            createdAt: $membership->getCreatedAt()->format(DATE_ATOM),
            frozenAt: $membership->getFrozenAt()?->format(DATE_ATOM),
            payment: PaymentResponse::fromEntity($membership->getPayment()),
        );
    }
}
