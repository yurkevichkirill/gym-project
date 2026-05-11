<?php

declare(strict_types=1);

namespace App\Booking\Service;

use App\Booking\DTO\BookingRequest;
use App\Booking\Entity\Booking;
use App\Booking\Enum\BookingStatusEnum;
use App\Booking\Repository\BookingRepository;
use App\Client\Entity\Client;
use App\Exception\InvalidBookingStatusException;
use App\Infrastructure\ClickHouse\Publisher\AnalyticsPublisher;
use App\Payment\Repository\PaymentRepository;
use App\Payment\Service\PaymentSettlementService;
use App\Trainer\Repository\TrainerRepository;
use App\Trainer\Service\TrainerManager;
use App\TrainerWorkTime\Repository\TrainerWorkTimeRepository;
use App\Training\Entity\Training;
use App\Training\Repository\TrainingRepository;
use DateMalformedStringException;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
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
    public function book(Client $client, BookingRequest $dto): Booking
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

            $worktime = $this->worktimeRepo->findOneBy([
                'trainer' => $trainer,
                'date' => new DateTimeImmutable($dto->date),
            ]);

            $this->bookingAvailabilityService->checkBookingAvailability($client, $trainer, $worktime, $dto->date, $dto->startTime, $dto->durationMinutes);

            $price = $this->trainerManager->countPrice($trainer, $dto->durationMinutes);

            return $this->entityManager->wrapInTransaction(function () use ($client, $dto, $trainer, $worktime, $price, $loggingContext) {
                $training = new Training();
                $training->setDurationMinutes($dto->durationMinutes);
                $training->setStartTime(new DateTimeImmutable($dto->startTime));
                $training->setTrainerWorkTime($worktime);
                $this->trainingRepo->create($training);

                $booking = new Booking();
                $booking->setClient($client);
                $booking->setTraining($training);
                $this->bookingRepo->create($booking);

                $payment = $this->paymentSettlementService->createBookingPayment(
                    $client,
                    $price,
                    $booking,
                    $trainer,
                );

                $this->paymentRepo->create($payment);

                $this->entityManager->flush();

                $this->analyticsPublisher->publish('booking.created', [
                    'client_id' => $client->getId(),
                    'trainer_id' => $trainer->getId(),
                    'booking_id' => $booking->getId(),
                    'price' => $price,
                    'payment_method' => $booking->getPayment()->getMethod()->value ?? 'unknown',
                ]);

                return $booking;
            });
        } catch (HttpExceptionInterface $e) {
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

    private function bookingEventContext(array $context, string $operation, string $outcome, array $extra = []): array
    {
        return $extra + $context + [
                'domain' => 'booking',
                'operation' => $operation,
                'outcome' => $outcome,
            ];
    }
}
