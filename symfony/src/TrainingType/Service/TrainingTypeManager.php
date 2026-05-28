<?php

declare(strict_types=1);

namespace App\TrainingType\Service;

use App\File\Service\FileManager;
use App\TrainingType\DTO\CreateTrainingTypeRequestDTO;
use App\TrainingType\DTO\UpdateTrainingTypeRequestDTO;
use App\TrainingType\Entity\TrainingType;
use App\TrainingType\Repository\TrainingTypeRepository;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final readonly class TrainingTypeManager
{
    public function __construct(
        private TrainingTypeRepository $trainingTypeRepo,
        private FileManager $fileManager,
        private FilesystemOperator $trainingTypesStorage,
        private EntityManagerInterface $entityManager,
    )
    {}

    public function create(CreateTrainingTypeRequestDTO $requestDto): TrainingType
    {
        $trainingType = new TrainingType();
        $trainingType->setName($requestDto->name);
        $trainingType->setDescription($requestDto->description);

        $this->trainingTypeRepo->create($trainingType);

        $this->entityManager->flush();

        return $trainingType;
    }

    public function update(UpdateTrainingTypeRequestDTO $requestDto, TrainingType $trainingType): TrainingType
    {
        if ($requestDto->name !== null) {
            $trainingType->setName($requestDto->name);
        }
        if ($requestDto->description !== null) {
            $trainingType->setDescription($requestDto->description);
        }

        $this->trainingTypeRepo->create($trainingType);

        $this->entityManager->flush();

        return $trainingType;
    }

    /**
     * @throws FilesystemException
     */
    public function updatePhoto(TrainingType $trainingType, UploadedFile $file): TrainingType
    {
        $oldPhotoPath = $trainingType->getPhotoPath();

        $newPhotoPath = $this->fileManager->upload(
            storage: $this->trainingTypesStorage,
            file: $file,
            directory: 'training_types',
            prefix: 'type'
        );

        $trainingType->setPhotoPath($newPhotoPath);
        $this->entityManager->flush();

        if ($oldPhotoPath !== null) {
            $this->fileManager->delete($this->trainingTypesStorage, $oldPhotoPath);
        }

        return $trainingType;
    }

    public function remove(TrainingType $trainingType): void
    {
        $this->trainingTypeRepo->remove($trainingType);

        $this->entityManager->flush();
    }
}
