<?php

declare(strict_types=1);

namespace App\Booking\Service;

use App\Booking\DTO\BookingRequest;
use App\Booking\Entity\Booking;
use App\Booking\Enum\BookingStatusEnum;
use App\Booking\Repository\BookingRepository;
use App\Client\Entity\Client;
use App\Client\Service\ClientManager;
use App\Exception\DateTimeAlreadyTakenException;
use App\Exception\NoActiveMembershipException;
use App\Membership\Enum\MembershipStatusEnum;
use App\Membership\Repository\MembershipRepository;
use App\Membership\Service\MembershipManager;
use App\Trainer\Repository\TrainerRepository;
use App\Trainer\Service\TrainerManager;
use App\TrainerWorkTime\Entity\TrainerWorkTime;
use App\TrainerWorkTime\Repository\TrainerWorkTimeRepository;
use App\TrainerWorkTime\Service\WorkTimeManager;
use App\Training\Entity\Training;
use App\Training\Repository\TrainingRepository;
use DateInterval;
use DateMalformedIntervalStringException;
use DateMalformedStringException;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class BookingManager
{
    public function __construct(
        private BookingRepository         $bookingRepo,
        private TrainingRepository        $trainingRepo,
        private TrainerWorkTimeRepository $worktimeRepo,
        private TrainerManager            $trainerManager,
        private ClientManager             $clientManager,
        private WorkTimeManager           $worktimeManager,
        private TrainerRepository         $trainerRepo,
        private MembershipManager         $membershipManager,
        private EntityManagerInterface    $entityManager,
    )
    {}

    /**
     * @throws DateMalformedStringException
     */
    public function book(Client $client, BookingRequest $dto): Booking
    {
        $this->clientManager->ensureNotBlocked($client);

        $trainer = $this->trainerRepo->find($dto->trainerId);

        if (!$trainer) {
            throw new NotFoundHttpException('Trainer not found');
        }

        $worktime = $this->worktimeRepo->findOneBy([
            'trainer' => $trainer,
            'date' => new DateTimeImmutable($dto->date),
        ]);

        if (!$worktime) {
            throw new NotFoundHttpException('Worktime not found');
        }

        $price = $this->trainerManager->countPrice($worktime->getTrainer(), $dto->durationMinutes);

        return $this->entityManager->wrapInTransaction(function () use ($client, $price, $worktime, $dto) {
            $this->validateTrainingTimeAvailable($worktime, $dto->startTime, $dto->durationMinutes);

            if (!$this->membershipManager->hasActiveMembership($client)) {
                throw new NoActiveMembershipException();
            }

            $payment = $this->clientManager->pay($client, $price, $worktime->getTrainer());

            $training = new Training();
            $training->setDurationMinutes($dto->durationMinutes);
            $training->setStartTime(new DateTimeImmutable($dto->startTime));
            $training->setTrainerWorkTime($worktime);
            $this->trainingRepo->create($training);

            $booking = new Booking();
            $booking->setClient($client);
            $booking->setTraining($training);
            $booking->setPayment($payment);
            $this->bookingRepo->create($booking);

            return $booking;
        });
    }

    public function cancelBooking(Client $client, Booking $booking): void
    {
        $this->entityManager->wrapInTransaction(function () use ($client, $booking) {
            $this->clientManager->refund($client, $booking->getPayment());
            $this->bookingRepo->remove($booking);
        });
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
