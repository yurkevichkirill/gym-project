<?php

declare(strict_types=1);

namespace App\Membership\DTO;

use App\Membership\Enum\MembershipStatusEnum;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class GetMembershipsRequestDTO
{
    public function __construct(
        #[Assert\Type('integer')]
        #[Assert\Positive]
        public ?int $membershipPlanId = null,

        #[Assert\Type('integer')]
        #[Assert\Positive]
        public ?int $clientId = null,

        public ?MembershipStatusEnum $status = null,

        #[Assert\Type('integer')]
        #[Assert\GreaterThanOrEqual(0)]
        public ?int $minVisits = null,

        #[Assert\Type('integer')]
        #[Assert\GreaterThanOrEqual(0)]
        public ?int $maxVisits = null,

        public string $sort = 'startDate:ASC',

        #[Assert\Positive]
        public int $page = 1,

        #[Assert\Positive]
        public int $limit = 20,
    )
    {}
}
