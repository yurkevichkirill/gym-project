<?php

declare(strict_types=1);

namespace App\Booking\Service;

use App\Booking\Enum\BookingStatusEnum;

interface BookingServiceInterface
{
    public function findBy(int $clientId, array $sort, ?BookingStatusEnum $status): array;
    public function generateCacheKey(int $clientId, array $sort, ?BookingStatusEnum $status): string;
}
