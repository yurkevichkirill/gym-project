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
    )
    {}

    /**
     * @throws DateMalformedStringException
     * @throws Throwable
     */
    public function book(Client $client, BookingRequest $dto): Booking
    {
        $context = $this->buildBookingContext($client, $dto);

        $this->bookingLogger->info('Booking process started', $this->bookingEventContext($context, 'book', 'started'));

        try {
            $this->userAvailabilityService->ensureNotBlocked($client);
        } catch (Throwable $e) {
            $this->bookingLogger->notice('Blocked user attempted booking', $this->bookingEventContext($context, 'book', 'rejected', [
                'exception_class' => $e::class,
                'exception' => $e,
            ]));

            throw $e;
        }

        $trainer = $this->trainerRepo->find($dto->trainerId);

        if (!$trainer) {
            $this->bookingLogger->warning('Trainer not found during booking', $this->bookingEventContext($context, 'book', 'rejected'));
            throw new NotFoundHttpException('Trainer not found');
        }

        $worktime = $this->worktimeRepo->findOneBy([
            'trainer' => $trainer,
            'date' => new DateTimeImmutable($dto->date),
        ]);

        if (!$worktime) {
            $this->bookingLogger->warning('Trainer worktime not found during booking', $this->bookingEventContext($context, 'book', 'rejected', [
                'trainer_id' => $trainer->getId(),
            ]));

            throw new NotFoundHttpException('Worktime not found');
        }

        $bookingDateTime = new DateTimeImmutable($dto->date . ' ' . $dto->startTime);
        $now = new DateTimeImmutable();

        if ($bookingDateTime <= $now) {
            $this->bookingLogger->notice('Attempt to book training in the past', $this->bookingEventContext($context, 'book', 'rejected', [
                'datetime' => $bookingDateTime->format('c'),
                'now' => $now->format('c'),
            ]));

            throw new BadRequestHttpException('Cannot book training in the past');
        }

        $price = $this->trainerManager->countPrice($worktime->getTrainer(), $dto->durationMinutes);
        $context = $context + [
            'trainer_id' => $trainer->getId(),
            'worktime_id' => $worktime->getId(),
            'price' => $price,
        ];

        $this->bookingLogger->info('Booking price calculated', $this->bookingEventContext($context, 'pricing', 'calculated'));

        return $this->entityManager->wrapInTransaction(function () use ($client, $price, $worktime, $dto, $trainer) {
            $context = $this->buildBookingContext($client, $dto) + [
                'trainer_id' => $trainer->getId(),
                'worktime_id' => $worktime->getId(),
                'price' => $price,
            ];

            try {
                $this->validateTrainingTimeAvailable($worktime, $dto->startTime, $dto->durationMinutes);

                if (!$this->visitingService->hasActiveMembership($client)) {
                    $this->bookingLogger->notice('Booking rejected: client has no active membership', $this->bookingEventContext($context, 'book', 'rejected'));
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

                $this->bookingLogger->info('Booking entity created', $this->bookingEventContext($context, 'book', 'persisted', [
                    'training_id' => $training->getId(),
                    'booking_id' => $booking->getId(),
                ]));

                if ($client->getBalance() >= $price) {
                    $this->bookingLogger->info('Booking payment route selected: balance', $this->bookingEventContext($context, 'payment_route', 'selected', [
                        'payment_method' => PaymentMethodEnum::BALANCE->value,
                        'client_balance' => $client->getBalance(),
                    ]));

                    $payment = $this->paymentService->createPayment(
                        $client,
                        $price,
                        PaymentCategoryEnum::TRAINER,
                        PaymentMethodEnum::BALANCE,
                        $trainer
                    );

                    $booking->setPayment($payment);

                    $this->paymentService->confirmPayment(
                        payment: $payment,
                        booking: $booking,
                    );

                    $this->bookingLogger->info('Booking payment confirmed from balance', $this->bookingEventContext($context, 'payment', 'confirmed', [
                        'booking_id' => $booking->getId(),
                        'payment_id' => $payment->getId(),
                    ]));
                } else {
                    $remaining = $price - $client->getBalance();

                    $this->bookingLogger->info('Booking payment route selected: card', $this->bookingEventContext($context, 'payment_route', 'selected', [
                        'payment_method' => PaymentMethodEnum::CARD->value,
                        'client_balance' => $client->getBalance(),
                        'remaining_amount' => $remaining,
                    ]));

                    $payment = $this->paymentService->createPayment(
                        $client,
                        $remaining,
                        PaymentCategoryEnum::TRAINER,
                        PaymentMethodEnum::CARD,
                        $trainer
                    );

                    $booking->setPayment($payment);
                }

                $this->entityManager->flush();

                $this->bookingLogger->info('Booking successfully completed', $this->bookingEventContext($context, 'book', 'succeeded', [
                    'booking_id' => $booking->getId(),
                    'payment_id' => $booking->getPayment()?->getId(),
                    'payment_method' => $booking->getPayment()?->getMethod()->value,
                    'payment_status' => $booking->getPayment()?->getStatus()->value,
                ]));

                return $booking;
            } catch (Throwable $e) {
                $this->bookingLogger->error('Booking failed', $this->bookingEventContext($context, 'book', 'failed', [
                    'exception_class' => $e::class,
                    'error' => $e->getMessage(),
                    'exception' => $e,
                ]));

                throw $e;
            }
        });
    }

    public function cancelBooking(Booking $booking, BookingStatusEnum $reason): void
    {
        $context = [
            'booking_id' => $booking->getId(),
            'reason' => $reason->value,
            'client_id' => $booking->getClient()?->getId(),
            'training_id' => $booking->getTraining()?->getId(),
            'payment_id' => $booking->getPayment()?->getId(),
            'payment_status' => $booking->getPayment()?->getStatus()?->value,
        ];

        $this->bookingLogger->info('Booking cancellation started', $this->bookingEventContext($context, 'cancel', 'started'));

        $this->entityManager->wrapInTransaction(function () use ($booking, $reason) {
            $context = [
                'booking_id' => $booking->getId(),
                'reason' => $reason->value,
                'client_id' => $booking->getClient()?->getId(),
                'training_id' => $booking->getTraining()?->getId(),
                'payment_id' => $booking->getPayment()?->getId(),
                'payment_status' => $booking->getPayment()?->getStatus()?->value,
            ];
            $payment = $booking->getPayment();

            try {
                if ($payment === null) {
                    $this->bookingLogger->warning('Booking cancellation without payment', $this->bookingEventContext($context, 'cancel', 'degraded'));
                    $booking->cancel($reason);

                    $this->bookingLogger->info('Booking canceled without payment refund flow', $this->bookingEventContext($context, 'cancel', 'succeeded'));

                    return;
                }

                if ($payment->getStatus() === PaymentStatusEnum::SUCCEEDED) {
                    $this->bookingLogger->info('Booking cancellation requires refund', $this->bookingEventContext($context, 'refund', 'started'));

                    $this->paymentService->refundPaymentViaStripe($payment);
                } else {
                    $this->bookingLogger->info('Booking cancellation requires payment intent cancel', $this->bookingEventContext($context, 'cancel_payment_intent', 'started'));

                    $this->paymentService->cancelPaymentWithStripeIntent($payment);
                }

                $booking->cancel($reason);

                $this->bookingLogger->info('Booking successfully canceled', $this->bookingEventContext($context, 'cancel', 'succeeded'));
            } catch (Throwable $e) {
                $this->bookingLogger->error('Booking cancellation failed', $this->bookingEventContext($context, 'cancel', 'failed', [
                    'exception_class' => $e::class,
                    'error' => $e->getMessage(),
                    'exception' => $e,
                ]));

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
            $this->bookingLogger->warning('Time slot already taken', [
                'domain' => 'booking',
                'operation' => 'validate_slot',
                'outcome' => 'rejected',
                'worktime_id' => $worktime->getId(),
                'trainer_id' => $worktime->getTrainer()->getId(),
                'date' => $worktime->getDate()?->format('Y-m-d'),
                'start_time' => $startTime,
                'duration_minutes' => $durationMinutes,
            ]);
            throw new DateTimeAlreadyTakenException();
        }
    }

    private function buildBookingContext(Client $client, BookingRequest $dto): array
    {
        return [
            'domain' => 'booking',
            'client_id' => $client->getId(),
            'trainer_id' => $dto->trainerId,
            'date' => $dto->date,
            'start_time' => $dto->startTime,
            'duration_minutes' => $dto->durationMinutes,
        ];
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
