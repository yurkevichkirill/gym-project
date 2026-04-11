<?php

declare(strict_types=1);

namespace App\TrainingType\Service;

use App\TrainingType\DTO\CreateTrainingTypeRequest;
use App\TrainingType\DTO\UpdateTrainingTypeRequest;
use App\TrainingType\Entity\TrainingType;
use App\TrainingType\Repository\TrainingTypeRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class TrainingTypeManager
{
    public function __construct(
        private TrainingTypeRepository $trainingTypeRepo,
        private EntityManagerInterface $entityManager,
    )
    {}

    public function create(CreateTrainingTypeRequest $requestDto): TrainingType
    {
        $trainingType = new TrainingType();
        $trainingType->setName($requestDto->name);
        $trainingType->setDescription($requestDto->description);
        $trainingType->setPhotoUrl($requestDto->photoUrl);

        $this->trainingTypeRepo->create($trainingType);

        $this->entityManager->flush();

        return $trainingType;
    }

    public function update(UpdateTrainingTypeRequest $requestDto, TrainingType $trainingType): TrainingType
    {
        if ($requestDto->name !== null) {
            $trainingType->setName($requestDto->name);
        }
        if ($requestDto->description !== null) {
            $trainingType->setDescription($requestDto->description);
        }
        if ($requestDto->photoUrl !== null) {
            $trainingType->setPhotoUrl($requestDto->photoUrl);
        }

        $this->trainingTypeRepo->create($trainingType);

        $this->entityManager->flush();

        return $trainingType;
    }

    public function remove(TrainingType $trainingType): void
    {
        $this->trainingTypeRepo->remove($trainingType);

        $this->entityManager->flush();
    }
}
