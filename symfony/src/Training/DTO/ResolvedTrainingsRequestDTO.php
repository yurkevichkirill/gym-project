<?php

declare(strict_types=1);

namespace App\Training\DTO;

use App\Booking\Enum\BookingStatusEnum;
use App\Client\Entity\Client;
use App\Trainer\Entity\Trainer;
use DateTimeImmutable;

final readonly class ResolvedTrainingsRequestDTO
{
    public const array ALLOWED_SORT_FIELDS = ['startTime', 'durationMinutes', 'clientId', 'date', 'status', 'bookedAt', 'isBusy'];

    public function __construct(
        public ?Trainer           $trainer = null,
        public ?Client            $client = null,
        public ?BookingStatusEnum $status = null,
        public ?DateTimeImmutable $date = null,
        public ?DateTimeImmutable $startTime = null,
        public ?int               $durationMinutes = null,
        public ?bool              $isBusy = null,
        public string             $sort,
        public int                $page = 1,
        public int                $limit = 20,
    )
    {}
}
