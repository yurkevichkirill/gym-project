<?php

declare(strict_types=1);

namespace App\Booking\Service;

use App\Booking\DTO\BookingRequest;
use App\Booking\Entity\Booking;
use App\Booking\Repository\BookingRepository;
use App\Client\Entity\Client;
use App\Exception\DateTimeAlreadyTakenException;
use App\TrainerWorkTime\Repository\TrainerWorkTimeRepository;
use App\TrainerWorkTime\Service\WorkTimeManager;
use App\Training\Entity\Training;
use App\Training\Repository\TrainingRepository;
use DateMalformedIntervalStringException;
use DateMalformedStringException;
use DateTimeImmutable;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;

final readonly class BookingManager
{
    public function __construct(
        private BookingRepository         $bookingRepo,
        private TrainingRepository        $trainingRepo,
        private TrainerWorkTimeRepository $worktimeRepo,
        private WorkTimeManager $worktimeManager,
    )
    {}

    /**
     * @throws OptimisticLockException
     * @throws DateMalformedStringException
     * @throws ORMException
     * @throws DateMalformedIntervalStringException|DateTimeAlreadyTakenException
     */
    public function book(Client $client, BookingRequest $dto): Booking
    {
        $worktime = $this->worktimeRepo->find($dto->worktimeId);

        if ($this->worktimeManager->isTimeAvailable($worktime, $dto->startTime, $dto->durationMinutes)) {

            $training = new Training();
            $training->setDurationMinutes($dto->durationMinutes);
            $training->setStartTime(new DateTimeImmutable($dto->startTime));
            $training->setTrainerWorkTime($worktime);
            $this->trainingRepo->create($training);

            $booking = new Booking();
            $booking->setClient($client);
            $booking->setTraining($training);
            $this->bookingRepo->create($booking);

            return $booking;
        } else {
            throw new DateTimeAlreadyTakenException();
        }
    }
}
