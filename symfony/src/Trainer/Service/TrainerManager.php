<?php

declare(strict_types=1);

namespace App\Trainer\Service;

use App\Booking\Enum\BookingStatusEnum;
use App\Booking\Repository\BookingRepository;
use App\File\Service\FileManager;
use App\Payment\Repository\PaymentRepository;
use App\RefreshToken\Repository\RefreshTokenRepository;
use App\Trainer\DTO\AdminUpdateTrainerRequestDTO;
use App\Trainer\DTO\CreateTrainerRequestDTO;
use App\Trainer\DTO\UpdateTrainerRequestDTO;
use App\Trainer\Entity\Trainer;
use App\Trainer\Exception\CannotDeleteTrainerException;
use App\Trainer\Repository\TrainerRepository;
use App\TrainingType\Exception\TrainingTypeNotFoundException;
use App\TrainingType\Repository\TrainingTypeRepository;
use App\User\Exception\UserAlreadyBlockedException;
use App\User\Exception\UserAlreadyDeletedException;
use App\User\Exception\UserAlreadyExistsException;
use App\User\Exception\UserAlreadyNotBlockedException;
use App\User\Exception\UserNotFoundException;
use App\User\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\FilesystemException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use League\Flysystem\FilesystemOperator;
use Throwable;
use App\Infrastructure\Doctrine\SoftDeleteableFilterScope;
use App\User\Exception\UserNotDeletedException;

final readonly class TrainerManager
{
    const int MIN_DURATION = 30;
    const int TRAINER_PRICE_DIVIDER = 2;
    public function __construct(
        private TrainerRepository $trainerRepo,
        private BookingRepository $bookingRepo,
        private UserRepository $userRepo,
        private TrainingTypeRepository $trainingTypeRepo,
        private PaymentRepository $paymentRepo,
        private UserPasswordHasherInterface $passwordHasher,
        private RefreshTokenRepository $refreshTokenRepo,
        private EntityManagerInterface $entityManager,
        private SoftDeleteableFilterScope $softDeleteableFilterScope,
        private FilesystemOperator $trainersStorage,
        private FileManager $fileManager,
    )
    {}

    public function create(CreateTrainerRequestDTO $requestDto): Trainer
    {
        $existingTrainer = $this->userRepo->findOneBy(['email' => $requestDto->email]);
        if ($existingTrainer !== null) {
            throw new UserAlreadyExistsException('User with this email already exists');
        }

        $existingTrainerByPhone = $this->userRepo->findOneBy(['phone' => $requestDto->phone]);
        if ($existingTrainerByPhone !== null) {
            throw new UserAlreadyExistsException('User with this phone already exists');
        }

        $trainer = new Trainer();

        $trainingType = $this->trainingTypeRepo->find($requestDto->trainingTypeId);

        if ($trainingType === null) {
            throw new TrainingTypeNotFoundException();
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
        $trainer->setAbout($requestDto->about);
        $trainer->setEducation($requestDto->education);
        $trainer->setPricePerHour($requestDto->pricePerHour);

        $this->trainerRepo->create($trainer);

        $this->entityManager->flush();

        return $trainer;
    }

    /**
     * @throws ConflictHttpException
     */
    public function updateByAdmin(AdminUpdateTrainerRequestDTO $requestDto, Trainer $trainer): Trainer
    {
        if ($requestDto->email !== null && $requestDto->email !== $trainer->getEmail()) {
            $existing = $this->userRepo->findOneBy(['email' => $requestDto->email]);
            if ($existing !== null) {
                throw new UserAlreadyExistsException('Email is already taken by another user.');
            }
            $trainer->setEmail($requestDto->email);
        }

        if ($requestDto->phone !== null && $requestDto->phone !== $trainer->getPhone()) {
            $existing = $this->userRepo->findOneBy(['phone' => $requestDto->phone]);
            if ($existing !== null) {
                throw new UserAlreadyExistsException('Phone number is already taken.');
            }
            $trainer->setPhone($requestDto->phone);
        }

        if ($requestDto->firstName !== null) {
            $trainer->setFirstName($requestDto->firstName);
        }

        if ($requestDto->lastName !== null) {
            $trainer->setLastName($requestDto->lastName);
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

        $this->entityManager->flush();

        return $trainer;
    }

    public function update(Trainer $trainer, UpdateTrainerRequestDTO $requestDto): Trainer
    {
        if ($requestDto->phone !== null) {
            $trainer->setPhone($requestDto->phone);
        }
        if ($requestDto->pricePerHour !== null) {
            $trainer->setPricePerHour($requestDto->pricePerHour);
        }

        $this->entityManager->flush();

        return $trainer;
    }

    /**
     * @throws FilesystemException
     * @throws Throwable
     */
    public function updatePhoto(Trainer $trainer, UploadedFile $file): Trainer
    {
        $oldPhotoPath = $trainer->getPhotoPath();

        $newPhotoPath = $this->fileManager->upload(
            storage: $this->trainersStorage,
            file: $file,
            directory: 'trainers',
            prefix: 'avatar'
        );

        try {
            $trainer->setPhotoPath($newPhotoPath);

            $this->entityManager->flush();
        } catch (Throwable $e) {
            $this->fileManager->delete($this->trainersStorage, $newPhotoPath);

            throw $e;
        }

        if ($oldPhotoPath !== null) {
            $this->fileManager->delete($this->trainersStorage, $oldPhotoPath);
        }

        return $trainer;
    }

    public function softDelete(Trainer $trainer): void
    {
        $trainerId = $trainer->getId();

        if ($trainerId === null) {
            throw new UserNotFoundException('Trainer not found');
        }

        $this->entityManager->wrapInTransaction(
            function () use ($trainerId): void {
                $lockedTrainer = $this->trainerRepo->findForUpdate($trainerId);

                if ($lockedTrainer === null) {
                    throw new UserNotFoundException('Trainer not found');
                }

                if ($lockedTrainer->getDeletedAt() !== null) {
                    throw new UserAlreadyDeletedException(
                        'Trainer already deleted'
                    );
                }

                if ($this->bookingRepo->existsForTrainerInStatuses(
                    $lockedTrainer,
                    [
                        BookingStatusEnum::PENDING,
                        BookingStatusEnum::SCHEDULED,
                    ],
                )) {
                    throw new CannotDeleteTrainerException(
                        'Cannot delete trainer with pending or scheduled bookings'
                    );
                }

                if ($this->paymentRepo->existsUnsettledForTrainer($lockedTrainer)) {
                    throw new CannotDeleteTrainerException(
                        'Cannot delete trainer with unsettled payments'
                    );
                }

                if ($lockedTrainer->getBalance() !== 0) {
                    throw new CannotDeleteTrainerException(
                        'Cannot delete trainer while balance is not zero'
                    );
                }

                $this->trainerRepo->remove($lockedTrainer);
                $this->refreshTokenRepo->removeAllByUser($lockedTrainer);
            }
        );
    }

    public function restore(int $trainerId): Trainer
    {
        return $this->entityManager->wrapInTransaction(
            function () use ($trainerId): Trainer {
                $trainer = $this->softDeleteableFilterScope->run(
                    fn (): ?Trainer => $this->trainerRepo->findForUpdate($trainerId),
                );

                if ($trainer === null) {
                    throw new UserNotFoundException('Trainer not found');
                }

                if ($trainer->getDeletedAt() === null) {
                    throw new UserNotDeletedException('Trainer is not deleted');
                }

                $trainer->setDeletedAt();

                return $trainer;
            },
        );
    }

    public function block(Trainer $trainer): Trainer
    {
        if ($trainer->getBlockedAt() !== null) {
            throw new UserAlreadyBlockedException('Trainer already blocked');
        }

        $trainer->setBlockedAt(new DateTimeImmutable());

        $this->entityManager->flush();

        return $trainer;
    }

    public function unblock(Trainer $trainer): Trainer
    {
        if ($trainer->getBlockedAt() === null) {
            throw new UserAlreadyNotBlockedException('Trainer is not currently blocked');
        }

        $trainer->setBlockedAt(null);
        $this->entityManager->flush();

        return $trainer;
    }

    public function countPrice(Trainer $trainer, int $durationMinutes): int
    {
        $pricePerHour = $trainer->getPricePerHour();

        $price = $durationMinutes / self::MIN_DURATION * $pricePerHour / self::TRAINER_PRICE_DIVIDER;

        return (int) round($price);
    }

    public function getAvailable(Trainer $trainer): Trainer
    {
        if ($trainer->getDeletedAt() !== null || $trainer->getBlockedAt() !== null) {
            throw new UserNotFoundException('Trainer not found');
        }

        return $trainer;
    }
}
