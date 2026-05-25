<?php

declare(strict_types=1);

namespace App\Payment\DTO;

use App\Client\Entity\Client;
use App\Payment\Enum\PaymentStatusEnum;
use App\Trainer\Entity\Trainer;
use DateTimeImmutable;

final readonly class ResolvedPaymentsRequestDTO
{
    public const array ALLOWED_SORT_FIELDS = ['amount', 'category', 'paidAt', 'status', 'isRefund', 'createdAt'];
    public function __construct(
        public ?Trainer           $trainer = null,
        public ?Client            $client = null,
        public ?int               $minAmount = null,
        public ?int               $maxAmount = null,
        public ?bool              $isRefund = null,
        public ?PaymentStatusEnum $status = null,
        public ?DateTimeImmutable $minCreatedAt = null,
        public ?DateTimeImmutable $maxCreatedAt = null,
        public string             $sort = 'paidAt:DESC',
        public int                $page = 1,
        public int                $limit = 20,
    ) {}
}
