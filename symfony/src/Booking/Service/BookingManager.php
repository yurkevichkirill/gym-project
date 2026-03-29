<?php

declare(strict_types=1);

namespace App\Booking\Service;

use App\Booking\DTO\BookingRequest;
use App\Booking\Entity\Booking;
use App\Booking\Repository\BookingRepository;
use App\Client\Entity\Client;
use App\Client\Service\ClientManager;
use App\Exception\DateTimeAlreadyTakenException;
use App\Exception\NoActiveMembershipException;
use App\Membership\Enum\MembershipStatusEnum;
use App\Membership\Repository\MembershipRepository;
use App\Trainer\Repository\TrainerRepository;
use App\Trainer\Service\TrainerManager;
use App\TrainerWorkTime\Entity\TrainerWorkTime;
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
        private MembershipRepository $membershipRepo,
        private TrainerManager            $trainerManager,
        private ClientManager             $clientManager,
        private WorkTimeManager           $worktimeManager,
        private TrainerRepository         $trainerRepo,
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
        $trainer = $this->trainerRepo->find($dto->trainerId);
        $worktime = $this->worktimeRepo->findOneBy([
            'trainer' => $trainer,
            'date' => new DateTimeImmutable($dto->date),
        ]);

        $this->validateTrainingTimeAvailable($worktime, $dto->startTime, $dto->durationMinutes);

        $this->validateActiveMembership($client);

        $price = $this->trainerManager->countPrice($worktime->getTrainer(), $dto->durationMinutes);
        $this->clientManager->pay($client, $price, $worktime->getTrainer());

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
    }

    private function validateActiveMembership(Client $client): void
    {
        $activeMembership = $this->membershipRepo->findOneBy([
            'client' => $client,
            'status' => MembershipStatusEnum::ACTIVE
        ]);

        if (!$activeMembership) {
            throw new NoActiveMembershipException();
        }
    }

    /**
     * @throws DateMalformedStringException
     * @throws DateMalformedIntervalStringException
     */
    private function validateTrainingTimeAvailable(TrainerWorkTime $worktime, string $startTime, int $durationMinutes): void
    {
        if (!$this->worktimeManager->isTimeAvailable($worktime, $startTime, $durationMinutes)) {
            throw new DateTimeAlreadyTakenException();
        }
    }

}
