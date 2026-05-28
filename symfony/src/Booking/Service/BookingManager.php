<?php

declare(strict_types=1);

namespace App\Booking\Service;

use App\Booking\DTO\BookingRequestDTO;
use App\Booking\Entity\Booking;
use App\Booking\Repository\BookingRepository;
use App\Client\Entity\Client;
use App\Infrastructure\ClickHouse\Publisher\AnalyticsPublisher;
use App\Payment\Repository\PaymentRepository;
use App\Payment\Service\PaymentSettlementService;
use App\Trainer\Entity\Trainer;
use App\Trainer\Exception\TrainerNotFoundException;
use App\Trainer\Repository\TrainerRepository;
use App\Trainer\Service\TrainerManager;
use App\TrainerWorkTime\Entity\TrainerWorkTime;
use App\TrainerWorkTime\Exception\TrainerWorktimeNotFoundException;
use App\TrainerWorkTime\Repository\TrainerWorkTimeRepository;
use App\Training\Entity\Training;
use App\Training\Repository\TrainingRepository;
use App\User\Exception\UserNotFoundException;
use DateMalformedStringException;
use DateTimeImmutable;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use DomainException;
use Psr\Log\LoggerInterface;
use Throwable;

final readonly class BookingManager
{
    public function __construct(
        private BookingRepository         $bookingRepo,
        private TrainingRepository        $trainingRepo,
        private TrainerWorkTimeRepository $worktimeRepo,
        private TrainerManager            $trainerManager,
        private TrainerRepository         $trainerRepo,
        private BookingAvailabilityService $bookingAvailabilityService,
        private PaymentSettlementService  $paymentSettlementService,
        private EntityManagerInterface    $entityManager,
        private LoggerInterface           $bookingLogger,
        private PaymentRepository         $paymentRepo,
        private AnalyticsPublisher        $analyticsPublisher,
    )
    {}

    /**
     * @throws DateMalformedStringException
     * @throws Throwable
     */
    public function book(Client $client, BookingRequestDTO $dto): Booking
    {
        $loggingContext = [
            'client_id' => $client->getId(),
            'trainer_id' => $dto->trainerId,
            'date' => $dto->date,
            'start_time' => $dto->startTime,
            'duration_minutes' => $dto->durationMinutes,
        ];

        try {
            $trainer = $this->trainerRepo->find($dto->trainerId);

            if ($trainer === null) {
                throw new TrainerNotFoundException();
            }

            $worktime = $this->worktimeRepo->findOneBy([
                'trainer' => $trainer,
                'date' => new DateTimeImmutable($dto->date),
            ]);

            if ($worktime === null) {
                throw new TrainerWorktimeNotFoundException();
            }

            $worktimeId = $worktime->getId();
            $clientId = $client->getId();
            $trainerId = $trainer->getId();

            $booking = $this->entityManager->wrapInTransaction(function () use ($clientId, $dto, $trainerId, $worktimeId) {
                $lockedClient = $this->entityManager->find(
                    Client::class,
                    $clientId,
                    LockMode::PESSIMISTIC_WRITE
                );
                if ($lockedClient === null) {
                    throw new UserNotFoundException("Client not found");
                }

                $lockedTrainer = $this->entityManager->find(
                    Trainer::class,
                    $trainerId,
                    LockMode::PESSIMISTIC_WRITE
                );
                if ($lockedTrainer === null) {
                    throw new UserNotFoundException("Trainer not found");
                }

                $lockedWorktime = $this->entityManager->find(
                    TrainerWorkTime::class,
                    $worktimeId,
                    LockMode::PESSIMISTIC_WRITE
                );
                if ($lockedWorktime === null) {
                    throw new TrainerWorktimeNotFoundException();
                }

                $this->bookingAvailabilityService->checkBookingAvailability(
                    $lockedClient,
                    $lockedWorktime,
                    $dto->date,
                    $dto->startTime,
                    $dto->durationMinutes
                );

                $price = $this->trainerManager->countPrice($lockedTrainer, $dto->durationMinutes);

                $training = new Training();
                $training->setDurationMinutes($dto->durationMinutes);
                $training->setStartTime(new DateTimeImmutable($dto->startTime));
                $training->setTrainerWorkTime($lockedWorktime);
                $this->trainingRepo->create($training);

                $booking = new Booking();
                $booking->setClient($lockedClient);
                $booking->setTraining($training);
                $this->bookingRepo->create($booking);

                $payment = $this->paymentSettlementService->createBookingPayment(
                    $lockedClient,
                    $price,
                    $booking,
                    $lockedTrainer,
                );

                return $booking;
            });

            try {
                $bookingPayment = $booking->getPayment();
                $paymentMethod = $bookingPayment?->getMethod()->value ?? 'unknown';

                $this->analyticsPublisher->publish('booking.created', [
                    'client_id' => $client->getId(),
                    'trainer_id' => $trainer->getId(),
                    'booking_id' => $booking->getId(),
                    'price' => $bookingPayment?->getAmount() ?? 0,
                    'payment_method' => $paymentMethod,
                ]);
            } catch (Throwable $e) {
                $this->bookingLogger->error('analytics.publish.failed', [
                    'error' => $e->getMessage(),
                    'booking_id' => $booking->getId(),
                ]);
            }

            return $booking;
        } catch (DomainException $e) {
            $this->bookingLogger->notice('booking.rejected', $this->bookingEventContext($loggingContext, 'book', 'rejected', [
                'reason' => $e::class,
            ]));

            throw $e;
        } catch (Throwable $e) {
            $this->bookingLogger->error('booking.failed', $this->bookingEventContext($loggingContext, 'book', 'failed', [
                'error' => $e->getMessage(),
                'exception_class' => $e::class,
            ]));

            throw $e;
        }
    }

    /**
     * @param array<string, scalar|null> $context
     * @param array<string, scalar|null> $extra
     * @return array<string, scalar|null>
     */
    private function bookingEventContext(array $context, string $operation, string $outcome, array $extra = []): array
    {
        return $extra + $context + [
                'domain' => 'booking',
                'operation' => $operation,
                'outcome' => $outcome,
            ];
    }
}
