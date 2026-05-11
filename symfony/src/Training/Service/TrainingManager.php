<?php

declare(strict_types=1);

namespace App\Training\Service;

use App\Booking\Enum\BookingStatusEnum;
use App\Booking\Service\BookingAvailabilityService;
use App\TrainerWorkTime\Repository\TrainerWorkTimeRepository;
use App\Training\DTO\TrainingUpdateRequest;
use App\Training\Entity\Training;
use DateMalformedIntervalStringException;
use DateMalformedStringException;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

final readonly class TrainingManager
{
    public function __construct(
        private TrainerWorkTimeRepository $worktimeRepo,
        private BookingAvailabilityService $bookingAvailabilityService,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $bookingLogger,
    )
    {}

    /**
     * @throws HttpExceptionInterface
     * @throws DateMalformedStringException
     * @throws Throwable
     * @throws DateMalformedIntervalStringException
     */
    public function update(Training $training, TrainingUpdateRequest $requestDto): Training
    {
        $loggingContext = [
            'client_id' => $training->getBooking()?->getClient() ?? "",
            'trainer_id' => $training->getTrainerWorkTime()?->getTrainer()?->getId() ?? "",
            'date' => $training->getTrainerWorkTime()?->getDate() ?? "",
            'start_time' => $training->getStartTime() ?? "",
            'duration_minutes' => $training->getDurationMinutes() ?? "",
        ];

        try {
            $client = $training->getBooking()->getClient();
            $trainer = $training->getTrainerWorkTime()->getTrainer();

            $newDate = $requestDto->date
                ? new DateTimeImmutable($requestDto->date)
                : $training->getTrainerWorkTime()->getDate();

            $newStartTime = $requestDto->startTime
                ?? $training->getStartTime()->format('H:i:s');

            $newWorktime = $this->worktimeRepo->findByDateForTrainer(
                $trainer,
                $newDate
            );

            $this->bookingAvailabilityService->checkUpdateBookingAvailability($training, $client, $newWorktime, $newDate, $newStartTime);

            return $this->entityManager->wrapInTransaction(function () use ($training, $newWorktime, $newStartTime, $loggingContext) {
                $training->setTrainerWorkTime($newWorktime);

                $training->setStartTime(new DateTimeImmutable($newStartTime));

                return $training;
            });
        } catch (HttpExceptionInterface $e) {
            $this->bookingLogger->notice('update.rejected', $this->bookingEventContext($loggingContext, 'update', 'rejected', [
                'reason' => $e::class,
            ]));

            throw $e;
        } catch (Throwable $e) {
            $this->bookingLogger->error('updating.failed', $this->bookingEventContext($loggingContext, 'update', 'failed', [
                'error' => $e->getMessage(),
                'exception_class' => $e::class,
            ]));

            throw $e;
        }
    }

    /**
     * @throws HttpExceptionInterface
     * @throws Throwable
     */
    public function complete(Training $training): Training
    {
        $loggingContext = [
            'client_id' => $training->getBooking()?->getClient() ?? "",
            'trainer_id' => $training->getTrainerWorkTime()?->getTrainer()?->getId() ?? "",
            'date' => $training->getTrainerWorkTime()?->getDate() ?? "",
            'start_time' => $training->getStartTime() ?? "",
            'duration_minutes' => $training->getDurationMinutes() ?? "",
        ];

        try {
            $this->bookingAvailabilityService->checkCompleteBookingAvailability($training);

            $training->getBooking()->setStatus(BookingStatusEnum::COMPLETED);

            $this->entityManager->flush();

            return $training;
        } catch (HttpExceptionInterface $e) {
            $this->bookingLogger->notice('complete.rejected', $this->bookingEventContext($loggingContext, 'complete', 'rejected', [
                'reason' => $e::class,
            ]));

            throw $e;
        } catch (Throwable $e) {
            $this->bookingLogger->error('complete.failed', $this->bookingEventContext($loggingContext, 'complete', 'failed', [
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
