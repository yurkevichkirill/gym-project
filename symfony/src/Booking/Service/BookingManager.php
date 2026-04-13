<?php

declare(strict_types=1);

namespace App\Booking\Service;

use App\Booking\DTO\BookingRequest;
use App\Booking\Entity\Booking;
use App\Booking\Repository\BookingRepository;
use App\Client\Entity\Client;
use App\Exception\DateTimeAlreadyTakenException;
use App\Exception\NoActiveMembershipException;
use App\Membership\Service\MembershipManager;
use App\Membership\Service\VisitingService;
use App\Payment\Service\PaymentService;
use App\Trainer\Repository\TrainerRepository;
use App\Trainer\Service\TrainerManager;
use App\TrainerWorkTime\Entity\TrainerWorkTime;
use App\TrainerWorkTime\Repository\TrainerWorkTimeRepository;
use App\Training\Entity\Training;
use App\Training\Repository\TrainingRepository;
use App\User\Service\AvailabilityService as UserAvailabilityService;
use App\TrainerWorkTime\Service\AvailabilityService as WorktimeAvailabilityService;
use DateMalformedIntervalStringException;
use DateMalformedStringException;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class BookingManager
{
    public function __construct(
        private BookingRepository         $bookingRepo,
        private TrainingRepository        $trainingRepo,
        private TrainerWorkTimeRepository $worktimeRepo,
        private TrainerManager            $trainerManager,
        private TrainerRepository         $trainerRepo,
        private VisitingService           $visitingService,
        private UserAvailabilityService   $userAvailabilityService,
        private WorktimeAvailabilityService $worktimeAvailabilityService,
        private PaymentService            $paymentService,
        private EntityManagerInterface    $entityManager,
    )
    {}

    /**
     * @throws DateMalformedStringException
     */
    public function book(Client $client, BookingRequest $dto): Booking
    {
        $this->userAvailabilityService->ensureNotBlocked($client);

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

        $bookingDateTime = new DateTimeImmutable($dto->date . ' ' . $dto->startTime);
        $now = new DateTimeImmutable();

        if ($bookingDateTime <= $now) {
            throw new BadRequestHttpException('Cannot book training in the past');
        }

        $price = $this->trainerManager->countPrice($worktime->getTrainer(), $dto->durationMinutes);

        return $this->entityManager->wrapInTransaction(function () use ($client, $price, $worktime, $dto) {
            $this->validateTrainingTimeAvailable($worktime, $dto->startTime, $dto->durationMinutes);

            if (!$this->visitingService->hasActiveMembership($client)) {
                throw new NoActiveMembershipException();
            }

            $payment = $this->paymentService->pay($client, $price, $worktime->getTrainer());

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
            $this->paymentService->refund($client, $booking->getPayment());
            $this->bookingRepo->remove($booking);
        });
    }

    /**
     * @throws DateMalformedStringException
     * @throws DateMalformedIntervalStringException
     */
    private function validateTrainingTimeAvailable(TrainerWorkTime $worktime, string $startTime, int $durationMinutes): void
    {
        if (!$this->worktimeAvailabilityService->isTimeAvailable($worktime, $startTime, $durationMinutes)) {
            throw new DateTimeAlreadyTakenException();
        }
    }

}
