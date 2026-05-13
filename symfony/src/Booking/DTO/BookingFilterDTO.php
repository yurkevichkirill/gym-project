<?php

declare(strict_types=1);

namespace App\Booking\DTO;

use App\Booking\Enum\BookingStatusEnum;
use App\Client\Entity\Client;
use App\Trainer\Entity\Trainer;
use DateTimeImmutable;

final readonly class BookingFilterDTO
{
    public function __construct(
        public ?Client $client,
        public ?Trainer $trainer,
        public ?BookingStatusEnum $status,
        public ?DateTimeImmutable $date,
        public ?DateTimeImmutable $startTime,
        public ?int $durationMinutes,
    ) {}
}
