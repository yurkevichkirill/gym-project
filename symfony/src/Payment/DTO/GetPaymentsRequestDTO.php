<?php

declare(strict_types=1);

namespace App\Payment\DTO;

use App\Payment\Enum\PaymentStatusEnum;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class GetPaymentsRequestDTO
{
    public function __construct(
        #[Assert\Type('integer')]
        #[Assert\Positive]
        public ?int               $trainerId = null,

        #[Assert\Type('integer')]
        #[Assert\Positive]
        public ?int               $clientId = null,

        #[Assert\Positive]
        public ?int               $minAmount = null,

        #[Assert\Positive]
        public ?int               $maxAmount = null,

        public ?bool              $isRefund = null,

        public ?PaymentStatusEnum $status = null,

        #[Assert\Date]
        public ?string            $minCreatedAt = null,

        #[Assert\Date]
        public ?string            $maxCreatedAt = null,

        public string             $sort = 'createdAt:DESC',

        #[Assert\Positive]
        public int                $page = 1,

        #[Assert\Positive]
        #[Assert\Range(max: 100)]
        public int                $limit = 20,
    ) {}
}
