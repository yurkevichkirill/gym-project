<?php

declare(strict_types=1);

namespace App\Tests\Booking\Service;

use App\Booking\Service\BookingAvailabilityService;
use App\TrainerWorkTime\Entity\TrainerWorkTime;
use App\Training\Entity\Training;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

final class BookingAvailabilityServiceTest extends TestCase
{
    public function testOldStartTimeDoesNotFreeSlotWhenReschedulingToDifferentWorktime(): void
    {
        $service = (new ReflectionClass(BookingAvailabilityService::class))->newInstanceWithoutConstructor();
        $oldWorktime = $this->worktime(1, '2026-07-20', '09:00:00', '12:00:00');
        $newWorktime = $this->worktime(2, '2026-07-21', '09:00:00', '12:00:00');
        $newWorktime->addTraining($this->busyTraining('10:00:00', 60));

        $oldStartTime = $this->isSameWorktime($service, $oldWorktime, $newWorktime)
            ? '10:00:00'
            : null;

        self::assertFalse($this->isTimeAvailable($service, $newWorktime, '10:00:00', 60, $oldStartTime));
    }

    public function testOldStartTimeFreesCurrentSlotWhenReschedulingWithinSameWorktime(): void
    {
        $service = (new ReflectionClass(BookingAvailabilityService::class))->newInstanceWithoutConstructor();
        $worktime = $this->worktime(1, '2026-07-20', '09:00:00', '12:00:00');
        $worktime->addTraining($this->busyTraining('10:00:00', 60));

        $oldStartTime = $this->isSameWorktime($service, $worktime, $worktime)
            ? '10:00:00'
            : null;

        self::assertTrue($this->isTimeAvailable($service, $worktime, '10:00:00', 60, $oldStartTime));
    }

    private function isSameWorktime(
        BookingAvailabilityService $service,
        TrainerWorkTime $currentWorktime,
        TrainerWorkTime $checkedWorktime,
    ): bool {
        $method = new ReflectionMethod($service, 'isSameWorktime');
        $result = $method->invoke($service, $currentWorktime, $checkedWorktime);
        self::assertIsBool($result);

        return $result;
    }

    private function isTimeAvailable(
        BookingAvailabilityService $service,
        TrainerWorkTime $worktime,
        string $startTime,
        int $durationMinutes,
        ?string $oldStartTime,
    ): bool {
        $method = new ReflectionMethod($service, 'isTimeAvailable');
        $result = $method->invoke($service, $worktime, $startTime, $durationMinutes, $oldStartTime);
        self::assertIsBool($result);

        return $result;
    }

    private function worktime(int $id, string $date, string $startTime, string $endTime): TrainerWorkTime
    {
        $worktime = new TrainerWorkTime();
        $this->setPrivateProperty($worktime, 'id', $id);
        $worktime->setDate(new DateTimeImmutable($date));
        $worktime->setStartTime(new DateTimeImmutable($startTime));
        $worktime->setEndTime(new DateTimeImmutable($endTime));

        return $worktime;
    }

    private function busyTraining(string $startTime, int $durationMinutes): Training
    {
        $training = new Training();
        $training->setStartTime(new DateTimeImmutable($startTime));
        $training->setDurationMinutes($durationMinutes);
        $training->setIsBusy(true);

        return $training;
    }

    private function setPrivateProperty(object $object, string $property, mixed $value): void
    {
        $reflectionProperty = new ReflectionProperty($object, $property);
        $reflectionProperty->setValue($object, $value);
    }
}
