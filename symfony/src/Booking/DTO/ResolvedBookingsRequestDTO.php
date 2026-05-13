<?php

declare(strict_types=1);

namespace App\Booking\DTO;

use App\Booking\Enum\BookingStatusEnum;
use App\Client\Entity\Client;
use App\Trainer\Entity\Trainer;
use DateTimeImmutable;

final readonly class ResolvedBookingsRequestDTO
{
    public function __construct(
        public ?Trainer           $trainer = null,
        public ?Client            $client = null,
        public ?BookingStatusEnum $status = null,
        public ?DateTimeImmutable $date = null,
        public ?DateTimeImmutable $startTime = null,
        public ?int               $durationMinutes = null,
        public array              $sort,
        public int                $page = 1,
        public int                $limit = 20,
    ) {}
}
