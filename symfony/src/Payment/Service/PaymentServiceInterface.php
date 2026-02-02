<?php

declare(strict_types=1);

namespace App\Payment\Service;

use App\Client\Entity\Client;
use App\Payment\Entity\Payment;
use App\Payment\Enum\PaymentCategoryEnum;
use App\Payment\Enum\PaymentStatusEnum;

interface PaymentServiceInterface
{
    public function pay(Client $client, Payment $payment): void;
    public function cancel(Payment $payment): void;
    public function findBy(array $sort, ?int $clientId, ?PaymentCategoryEnum $category, ?PaymentStatusEnum $status): array;
}
