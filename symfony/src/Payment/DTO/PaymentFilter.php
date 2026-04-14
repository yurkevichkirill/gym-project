<?php

declare(strict_types=1);

namespace App\Payment\DTO;

use App\Client\Entity\Client;
use App\Payment\Enum\PaymentStatusEnum;
use App\Trainer\Entity\Trainer;
use DateTimeImmutable;

final readonly class PaymentFilter
{
    public function __construct(
        public ?Client $client,
        public ?Trainer $trainer,
        public ?int $minAmount,
        public ?int $maxAmount,
        public ?bool $isRefund,
        public ?PaymentStatusEnum $status,
        public ?DateTimeImmutable $minCreatedAt,
        public ?DateTimeImmutable $maxCreatedAt,
    ) {}
}
