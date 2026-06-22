<?php

declare(strict_types=1);

namespace App\Membership\DTO;

use App\Membership\Entity\Membership;
use App\Membership\Enum\MembershipStatusEnum;
use App\MembershipPlan\DTO\MembershipPlanResponseDTO;
use App\Payment\DTO\PaymentResponseDTO;
use LogicException;

final readonly class MembershipResponseDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public int $durationDays,
        public ?int $sessionLimit,
        public ?MembershipPlanResponseDTO $membershipPlan,
        public ?string $startDate,
        public ?string $endDate,
        public MembershipStatusEnum $status,
        public int $visits,
        public string $createdAt,
        public ?string $frozenAt,
        public PaymentResponseDTO $payment,
    )
    {}

    public static function fromEntity(Membership $membership): self
    {
        $id = $membership->getId();
        $plan = $membership->getPlan();
        $payment = $membership->getPayment();

        if ($id === null || $payment === null) {
            throw new LogicException('Membership is not fully initialized.');
        }

        return new self(
            id: $id,
            name: $membership->getName(),
            durationDays: $membership->getDurationDays(),
            sessionLimit: $membership->getSessionLimit(),
            membershipPlan: $plan !== null ? MembershipPlanResponseDTO::fromEntity($plan) : null,
            startDate: $membership->getStartDate()?->format('Y-m-d'),
            endDate: $membership->getEndDate()?->format('Y-m-d'),
            status: $membership->getStatus(),
            visits: $membership->getVisits(),
            createdAt: $membership->getCreatedAt()->format(DATE_ATOM),
            frozenAt: $membership->getFrozenAt()?->format(DATE_ATOM),
            payment: PaymentResponseDTO::fromEntity($payment),
        );
    }
}
