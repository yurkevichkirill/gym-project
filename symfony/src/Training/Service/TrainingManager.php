<?php

declare(strict_types=1);

namespace App\Training\Service;

use App\Exception\DateRescheduledException;
use App\TrainerWorkTime\Repository\TrainerWorkTimeRepository;
use App\Training\DTO\TrainingRequest;
use App\Training\Entity\Training;
use App\Client\Service\AvailabilityService as ClientAvailabilityService;
use App\TrainerWorkTime\Service\AvailabilityService as WorktimeAvailabilityService;
use App\Training\Repository\TrainingRepository;
use DateInterval;
use DateMalformedIntervalStringException;
use DateMalformedStringException;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class TrainingManager
{
    const int MIN_DAY_CHANGE = 1;
    public function __construct(
        private TrainingRepository $trainingRepo,
        private TrainerWorkTimeRepository $worktimeRepo,
        private ClientAvailabilityService $clientAvailabilityService,
        private WorktimeAvailabilityService $worktimeAvailabilityService,
        private EntityManagerInterface $entityManager,
    )
    {}

    /**
     * @throws DateMalformedStringException
     * @throws DateMalformedIntervalStringException
     * @throws DateRescheduledException
     */
    public function update(Training $training, TrainingRequest $requestDto): Training
    {
        $oldStartTime = $training->getStartTime()->format("H:i:s");
        $oldDurationMinutes = $training->getDurationMinutes();
        $newStartTime = $requestDto->startTime ?? $training->getStartTime();
        $newDurationMinutes = $requestDto->durationMinutes ?? $training->getDurationMinutes();
        $newDate = $requestDto->date ? new DateTimeImmutable($requestDto->date) : $training->getTrainerWorkTime()->getDate();
        $client = $training->getBooking()->getClient();

        if ($newDate->format('Y-m-d') === $training->getTrainerWorkTime()->getDate()->format("Y-m-d")) {
            $worktime = $training->getTrainerWorkTime();
            if(!$this->worktimeAvailabilityService->isTimeAvailable($worktime, $newStartTime, $newDurationMinutes, $oldStartTime, $oldDurationMinutes)) {
                throw new DateRescheduledException("OurTrainer doesn't work at this time");
            }
            if(!$this->clientAvailabilityService->isClientAvailableInDate($client, $newDate, $newStartTime, $newDurationMinutes, $oldStartTime)) {
                throw new DateRescheduledException("Client already have training at this time");
            }

            $training->setStartTime(new DateTimeImmutable($newStartTime));
            $training->setDurationMinutes($newDurationMinutes);

            $this->trainingRepo->save();
        } else if  ($newDate->format("Y-m-d") < new DateTimeImmutable()->add(new DateInterval('P' . self::MIN_DAY_CHANGE . 'D'))->format('Y-m-d')) {
            throw new DateRescheduledException("The minimum reschedule date must be no earlier than tomorrow.");
        } else {
            $newWorktime = $this->worktimeRepo->findOneBy(['date' => $newDate]);
            if (is_null($newWorktime)) {
                throw new NotFoundHttpException("There is no work time for this date");
            }

            $training->setTrainerWorkTime($newWorktime);
            if (!$this->worktimeAvailabilityService->isTimeAvailable($newWorktime, $newStartTime, $newDurationMinutes, $oldStartTime, $oldDurationMinutes)) {
                throw new DateRescheduledException("OurTrainer doesn't work at this time");
            }
            if (!$this->clientAvailabilityService->isClientAvailableInDate($client, $newDate, $newStartTime, $newDurationMinutes,  $oldStartTime)) {
                throw new DateRescheduledException("Client already have training at this time");
            }

            $training->setStartTime(new DateTimeImmutable($newStartTime));
            $training->setDurationMinutes($newDurationMinutes);

            $this->entityManager->flush();
        }

        return $training;
    }
}
