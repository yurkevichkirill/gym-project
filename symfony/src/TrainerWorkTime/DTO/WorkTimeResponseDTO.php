<?php

declare(strict_types=1);

namespace App\TrainerWorkTime\DTO;

use App\TrainerWorkTime\Entity\TrainerWorkTime;
use LogicException;

final readonly class WorkTimeResponseDTO
{
    public function __construct(
        public int $id,
        public int $trainerId,
        public int $trainingTypeId,
        public string $date,
        /** @var list<array{start: string, end: string}> */
        public array $freeSlots,
    )
    {}

    public static function fromEntity(TrainerWorkTime $worktime): self
    {
        $id = $worktime->getId();
        $trainerId = $worktime->getTrainer()->getId();
        $trainingType = $worktime->getTrainer()->getTrainingType();
        $trainingTypeId = $trainingType?->getId();

        if ($id === null || $trainerId === null || $trainingTypeId === null) {
            throw new LogicException('Worktime is not fully initialized.');
        }

        return new self(
            id: $id,
            trainerId: $trainerId,
            trainingTypeId: $trainingTypeId,
            date:  $worktime->getDate()->format('Y-m-d'),
            freeSlots: $worktime->getFreeSlots(),
        );
    }
}
