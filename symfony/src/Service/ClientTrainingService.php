<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Client;
use App\Entity\Payment;
use App\Entity\Trainer;
use App\Entity\Training;
use App\Enum\DayOfWeekEnum;
use App\Enum\PaymentCategoryEnum;
use App\Enum\PaymentStatusEnum;

class ClientTrainingService
{

    /**
     * @throws \DateMalformedStringException
     */
    public function getAvailable(Trainer $trainer, DayOfWeekEnum $dayOfWeek): array
    {
        $trainerAvailabilities = (array) $trainer->getTrainerAvailabilities();
        $trainings = (array) $trainer->getTrainings();
        $dayAvailabilities = array_filter($trainerAvailabilities, fn($item) => $item->getDayOfWeek() === $dayOfWeek)[0];
        $dayTrainings = array_filter($trainings, fn($item) => $item->getDayOfWeek() === $dayOfWeek);

        $startTrainerTime = $dayAvailabilities->getStartTime();
        $endTrainerTime = $dayAvailabilities->getEndTime();

        $trainings = [];
        foreach ($dayTrainings as $dayTraining) {
            $startTrainingTime = $dayTraining->getStartTime();
            $duration = $dayTraining->getDurationMinutes();
            $endTrainingTime = $startTrainingTime->add(new \DateTimeImmutable('PT' . $duration . 'M'));

            $trainings[$startTrainingTime] = $endTrainingTime;
        }

        ksort($trainings);
        $available = [];
        $startPeriod = $startTrainerTime;
        foreach ($trainings as $startTrainingTime => $endTrainingTime) {
            $available[$startPeriod] = $startTrainingTime;
            $startPeriod = $endTrainingTime;
        }
        $available[$startPeriod] = $endTrainerTime;

        return $available;
    }

    /**
     * @throws \DateMalformedStringException
     * @throws \DateMalformedIntervalStringException
     */
    public function enrollOnTraining(Trainer $trainer, Client $client, DayOfWeekEnum $dayOfWeek, \DateTimeImmutable $startTrainingTime, int $duration): void
    {
        $endTrainingTime = $startTrainingTime->add(new \DateInterval('PT' . $duration . 'M')) ;
        $available = $this->getAvailable($trainer, $dayOfWeek);
        if($this->isTimeAvailable($available, $startTrainingTime, $endTrainingTime)) {
            $payment = new Payment();
            $payment->setClient($client);
            $payment->setAmount($trainer->getPrice());
            $payment->setCategory(PaymentCategoryEnum::TRAINER);

        }
    }

    public function isTimeAvailable(array $available, \DateTimeImmutable $startTrainingTime, \DateTimeImmutable $endTrainingTime): bool
    {
        $startTimes = array_keys($available);
        foreach ($available as $startPeriod => $endPeriod) {
            if($startTrainingTime >= $startPeriod && $endTrainingTime <= $endPeriod) {

                return true;
            }
        }

        return false;
    }
}
