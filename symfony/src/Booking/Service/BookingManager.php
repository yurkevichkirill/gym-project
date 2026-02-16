<?php

declare(strict_types=1);

namespace App\Booking\Service;

use App\Booking\DTO\BookingRequest;
use App\Booking\Entity\Booking;
use App\Booking\Repository\BookingRepository;
use App\Client\Entity\Client;
use App\Exception\TimeAlreadyTakenException;
use App\TrainerWorkTime\Entity\TrainerWorkTime;
use App\TrainerWorkTime\Repository\TrainerWorkTimeRepository;
use App\Training\Entity\Training;
use App\Training\Repository\TrainingRepository;
use DateInterval;
use DateMalformedIntervalStringException;
use DateMalformedStringException;
use DateTimeImmutable;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class BookingManager
{
    public function __construct(
        private BookingRepository         $bookingRepo,
        private TrainingRepository        $trainingRepo,
        private TrainerWorkTimeRepository $worktimeRepo,
    )
    {}

    /**
     * @throws OptimisticLockException
     * @throws DateMalformedStringException
     * @throws ORMException
     * @throws DateMalformedIntervalStringException|TimeAlreadyTakenException
     */
    public function book(Client $client, BookingRequest $dto): Booking
    {
        $startTime = new DateTimeImmutable($dto->startTime);
        $endTime = $startTime->add(new DateInterval('PT' . $dto->durationMinutes . 'M'));

        $worktime = $this->worktimeRepo->find($dto->worktimeId);
        $freeSlots = $worktime->getFreeSlots();
        foreach ($freeSlots as $slot) {
            if($this->isSlotAvailable($slot, $startTime, $endTime)) {
                $training = new Training();
                $training->setDurationMinutes($dto->durationMinutes);
                $training->setStartTime($startTime);
                $training->setTrainerWorkTime($worktime);
                $this->trainingRepo->create($training);

                $booking = new Booking();
                $booking->setClient($client);
                $booking->setTraining($training);
                $this->bookingRepo->create($booking);

                return $booking;
            }
        }

        throw new TimeAlreadyTakenException();
    }

    private function isSlotAvailable(array $slot, DateTimeImmutable $startTime, DateTimeImmutable $endTime): bool
    {
        return $startTime >= $slot['start'] && $endTime <= $slot['end'];
    }
}
