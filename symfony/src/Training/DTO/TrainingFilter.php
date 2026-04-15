<?php

declare(strict_types=1);

namespace App\Training\DTO;

use App\Client\Entity\Client;
use App\Trainer\Entity\Trainer;
use DateTimeImmutable;

final readonly class TrainingFilter
{
    public function __construct(
        public ?Trainer           $trainer,
        public ?Client            $client,
        public ?DateTimeImmutable $date,
        public ?int               $durationMinutes,
        public ?DateTimeImmutable $startTime,
        public ?string            $status,
    ) {}
}
