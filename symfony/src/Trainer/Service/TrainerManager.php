<?php

declare(strict_types=1);

namespace App\Trainer\Service;

use App\Trainer\DTO\AdminUpdateTrainerRequest;
use App\Trainer\DTO\CreateTrainerRequest;
use App\Trainer\DTO\UpdateTrainerRequest;
use App\Trainer\Entity\Trainer;
use App\Trainer\Repository\TrainerRepository;
use App\TrainingType\Repository\TrainingTypeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class TrainerManager
{
    const int MIN_DURATION = 30;
    const int TRAINER_PRICE_DIVIDER = 2;
    public function __construct(
        private TrainerRepository $trainerRepo,
        private TrainingTypeRepository $trainingTypeRepo,
        private UserPasswordHasherInterface $passwordHasher,
        private EntityManagerInterface $entityManager,
    )
    {}

    public function create(CreateTrainerRequest $requestDto): Trainer
    {
        $trainer = new Trainer();

        $trainingType = $this->trainingTypeRepo->find($requestDto->trainingTypeId);

        if ($trainingType === null) {
            throw new NotFoundHttpException("Training type not found");
        }

        $trainer->setTrainingType($trainingType);

        $trainer->setFirstName($requestDto->firstName);
        $trainer->setLastName($requestDto->lastName);
        $trainer->setPhone($requestDto->phone);
        $trainer->setEmail($requestDto->email);

        $plaintextPassword = $requestDto->password;
        $hashedPassword = $this->passwordHasher->hashPassword(
            $trainer,
            $plaintextPassword
        );

        $trainer->setPassword($hashedPassword);
        $trainer->setPhotoUrl($requestDto->photoUrl);
        $trainer->setAbout($requestDto->about);
        $trainer->setEducation($requestDto->education);
        $trainer->setPricePerHour($requestDto->pricePerHour);

        $this->trainerRepo->create($trainer);

        $this->entityManager->flush();

        return $trainer;
    }

    public function updateByAdmin(Trainer $trainer, AdminUpdateTrainerRequest $requestDto): Trainer
    {
        if ($requestDto->firstName !== null) {
            $trainer->setFirstName($requestDto->firstName);
        }

        if ($requestDto->lastName !== null) {
            $trainer->setLastName($requestDto->lastName);
        }

        if ($requestDto->email !== null) {
            $trainer->setEmail($requestDto->email);
        }

        if ($requestDto->phone !== null) {
            $trainer->setPhone($requestDto->phone);
        }

        if ($requestDto->password !== null) {
            $hashed = $this->passwordHasher->hashPassword($trainer, $requestDto->password);
            $trainer->setPassword($hashed);
        }

        if ($requestDto->pricePerHour !== null) {
            $trainer->setPricePerHour($requestDto->pricePerHour);
        }

        if ($requestDto->education !== null) {
            $trainer->setEducation($requestDto->education);
        }

        if ($requestDto->photoUrl !== null) {
            $trainer->setPhotoUrl($requestDto->photoUrl);
        }

        if ($requestDto->balance !== null) {
            $trainer->setBalance($requestDto->balance);
        }

        $this->entityManager->flush();

        return $trainer;
    }

    public function update(Trainer $trainer, UpdateTrainerRequest $requestDto): Trainer
    {
        if($requestDto->phone) {
            $trainer->setPhone($requestDto->phone);
        }
        if($requestDto->pricePerHour) {
            $trainer->setPricePerHour($requestDto->pricePerHour);
        }

        $this->entityManager->flush();

        return $trainer;
    }

    public function softDelete(Trainer $trainer): void
    {
        $this->trainerRepo->remove($trainer);

        $this->entityManager->flush();
    }

    public function countPrice(Trainer $trainer, int $durationMinutes): float
    {
        $pricePerHour = (float) $trainer->getPricePerHour();

        return $durationMinutes / self::MIN_DURATION * $pricePerHour / self::TRAINER_PRICE_DIVIDER;
    }
}
