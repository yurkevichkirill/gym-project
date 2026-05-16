<?php

declare(strict_types=1);

namespace App\TrainerWorkTime\DTO;

use App\Trainer\Entity\Trainer;
use App\TrainerWorkTime\Entity\TrainerWorkTime;
use DateTimeImmutable;

final readonly class WorkTimeResponseDTO
{
    public function __construct(
        public int $id,
        public int $trainerId,
        public int $trainingTypeId,
        public string $date,
        public array $freeSlots,
    )
    {}

    /**
     * @throws \DateMalformedIntervalStringException
     */
    public static function fromEntity(TrainerWorkTime $worktime): self
    {
        return new self(
            id: $worktime->getId(),
            trainerId: $worktime->getTrainer()->getId(),
            trainingTypeId: $worktime->getTrainer()->getTrainingType()->getId(),
            date:  $worktime->getDate()->format("Y-m-d"),
            freeSlots: $worktime->getFreeSlots(),
        );
    }
}
