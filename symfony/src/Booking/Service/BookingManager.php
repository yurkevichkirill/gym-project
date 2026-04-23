<?php

declare(strict_types=1);

namespace App\Booking\Service;

use App\Booking\DTO\BookingRequest;
use App\Booking\Entity\Booking;
use App\Booking\Enum\BookingStatusEnum;
use App\Booking\Repository\BookingRepository;
use App\Client\Entity\Client;
use App\Exception\DateTimeAlreadyTakenException;
use App\Exception\NoActiveMembershipException;
use App\Infrastructure\ClickHouse\Publisher\AnalyticsPublisher;
use App\Membership\Service\VisitingService;
use App\Payment\Enum\PaymentCategoryEnum;
use App\Payment\Enum\PaymentMethodEnum;
use App\Payment\Enum\PaymentStatusEnum;
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
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

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
        private LoggerInterface           $bookingLogger,
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
            if ($bookingDateTime <= new DateTimeImmutable()) {
                throw new BadRequestHttpException('Cannot book training in the past');
            }

            $price = $this->trainerManager->countPrice($trainer, $dto->durationMinutes);

            return $this->entityManager->wrapInTransaction(function () use ($client, $dto, $trainer, $worktime, $price, $loggingContext) {

                $this->validateTrainingTimeAvailable($worktime, $dto->startTime, $dto->durationMinutes);

                if (!$this->visitingService->hasActiveMembership($client)) {
                    throw new NoActiveMembershipException();
                }

                $training = new Training();
                $training->setDurationMinutes($dto->durationMinutes);
                $training->setStartTime(new DateTimeImmutable($dto->startTime));
                $training->setTrainerWorkTime($worktime);
                $this->trainingRepo->create($training);

                $booking = new Booking();
                $booking->setClient($client);
                $booking->setTraining($training);
                $this->bookingRepo->create($booking);

                if ($client->getBalance() >= $price) {
                    $payment = $this->paymentService->createPayment(
                        $client,
                        $price,
                        PaymentCategoryEnum::TRAINER,
                        PaymentMethodEnum::BALANCE,
                        $trainer
                    );

                    $booking->setPayment($payment);

                    $this->paymentService->confirmPayment($payment, null, $booking);
                } else {
                    $payment = $this->paymentService->createPayment(
                        $client,
                        $price - $client->getBalance(),
                        PaymentCategoryEnum::TRAINER,
                        PaymentMethodEnum::CARD,
                        $trainer
                    );

                    $booking->setPayment($payment);
                }

                $this->entityManager->flush();

                $this->bookingLogger->info('booking.succeeded', $this->bookingEventContext($loggingContext, 'book', 'succeeded', [
                    'booking_id' => $booking->getId(),
                    'trainer_id' => $trainer->getId(),
                    'price' => $price,
                ]));

                $this->analyticsPublisher->publish('booking.created', [
                    'client_id' => $client->getId(),
                    'trainer_id' => $trainer->getId(),
                    'booking_id' => $booking->getId(),
                    'price' => $price,
                    'payment_method' => $booking->getPayment()->getMethod()->value ?? 'unknown',
                ]);

                return $booking;
            });

        } catch (NoActiveMembershipException|DateTimeAlreadyTakenException|BadRequestHttpException|NotFoundHttpException $e) {
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

    public function cancelBooking(Booking $booking, BookingStatusEnum $reason): void
    {
        $loggingContext = [
            'booking_id' => $booking->getId(),
            'client_id' => $booking->getClient()?->getId(),
            'reason' => $reason->value,
        ];

        $analyticalContext = [
            'client_id' => $booking->getClient()->getId(),
            'trainer_id' => $booking->getTraining()->getTrainerWorkTime()->getTrainer()->getId(),
            'booking_id' => $booking->getId(),
            'price' => $booking->getPayment()->getAmount(),
            'payment_method' => $booking->getPayment()->getMethod()->value ?? 'unknown',
        ];

        $this->entityManager->wrapInTransaction(function () use ($booking, $reason, $loggingContext, $analyticalContext) {

            try {
                $payment = $booking->getPayment();

                if ($payment !== null) {
                    if ($payment->getStatus() === PaymentStatusEnum::SUCCEEDED) {
                        if ($payment->getMethod() === PaymentMethodEnum::CARD) {
                            $this->paymentService->refundPaymentViaStripe($payment);
                        } else {
                            $this->paymentService->refundPayment($payment);
                        }
                    } else {
                        $this->paymentService->cancelPaymentWithStripeIntent($payment);
                    }
                }

                $booking->cancel($reason);

                $this->bookingLogger->info('booking.cancel.succeeded',
                    $this->bookingEventContext($loggingContext, 'cancel', 'succeeded')
                );

                $this->analyticsPublisher->publish(
                    'booking.canceled',
                    $analyticalContext,
                );

            } catch (Throwable $e) {
                $this->bookingLogger->error('booking.cancel.failed',
                    $this->bookingEventContext($loggingContext, 'cancel', 'failed', [
                        'error' => $e->getMessage(),
                        'exception_class' => $e::class,
                    ])
                );

                throw $e;
            }
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

    private function bookingEventContext(array $context, string $operation, string $outcome, array $extra = []): array
    {
        return $extra + $context + [
                'domain' => 'booking',
                'operation' => $operation,
                'outcome' => $outcome,
            ];
    }
}
