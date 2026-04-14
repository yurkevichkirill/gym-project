<?php

declare(strict_types=1);

namespace App\Payment\DTO;

final readonly class GetPayments
{
    public function __construct(
        public array $sort,
        public PaymentFilter $filter,
        public int $page = 1,
        public int $limit = 20,
    ) {}
}
