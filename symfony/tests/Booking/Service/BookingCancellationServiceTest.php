<?php

declare(strict_types=1);

namespace App\Tests\Booking\Service;

use App\Booking\Entity\Booking;
use App\Booking\Exception\InvalidBookingStatusException;
use App\Booking\Service\BookingCancellationService;
use App\Client\Entity\Client;
use App\Trainer\Entity\Trainer;
use App\TrainerWorkTime\Entity\TrainerWorkTime;
use App\Training\Entity\Training;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

final class BookingCancellationServiceTest extends TestCase
{
    public function testClientCannotCancelScheduledBookingAfterCancellationDeadline(): void
    {
        $service = (new ReflectionClass(BookingCancellationService::class))->newInstanceWithoutConstructor();
        $startAt = new DateTimeImmutable('+1 hour');
        $booking = $this->bookingAt($startAt, $startAt->format('H:i:s'));

        $this->expectException(InvalidBookingStatusException::class);
        $this->expectExceptionMessage('Client cancellation deadline has passed');

        $this->assertClientCanCancelScheduledBooking($service, $booking, new Client());
    }

    public function testClientCanCancelScheduledBookingBeforeCancellationDeadline(): void
    {
        $service = (new ReflectionClass(BookingCancellationService::class))->newInstanceWithoutConstructor();
        $startAt = new DateTimeImmutable('+3 hours');
        $booking = $this->bookingAt($startAt, $startAt->format('H:i:s'));

        $this->assertClientCanCancelScheduledBooking($service, $booking, new Client());

        self::addToAssertionCount(1);
    }

    public function testTrainerIsNotBlockedByClientCancellationDeadline(): void
    {
        $service = (new ReflectionClass(BookingCancellationService::class))->newInstanceWithoutConstructor();
        $booking = $this->bookingAt(new DateTimeImmutable('yesterday'), '10:00:00');

        $this->assertClientCanCancelScheduledBooking($service, $booking, new Trainer());

        self::addToAssertionCount(1);
    }


    public function testCompletedBookingCannotPassScheduledCancellationStatusCheck(): void
    {
        $service = (new ReflectionClass(BookingCancellationService::class))->newInstanceWithoutConstructor();
        $booking = $this->bookingAt(new DateTimeImmutable('tomorrow'), '10:00:00');
        $booking->setStatus(\App\Booking\Enum\BookingStatusEnum::COMPLETED);

        $this->expectException(InvalidBookingStatusException::class);
        $this->expectExceptionMessage('Booking with status "completed" cannot be canceled');

        $this->assertScheduledBookingCanBeCanceled($service, $booking);
    }

    private function assertScheduledBookingCanBeCanceled(
        BookingCancellationService $service,
        Booking $booking,
    ): void {
        $method = new ReflectionMethod($service, 'assertScheduledBookingCanBeCanceled');
        $method->invoke($service, $booking);
    }

    private function assertClientCanCancelScheduledBooking(
        BookingCancellationService $service,
        Booking $booking,
        Client|Trainer $actor,
    ): void {
        $method = new ReflectionMethod($service, 'assertClientCanCancelScheduledBooking');
        $method->invoke($service, $booking, $actor);
    }

    private function bookingAt(DateTimeImmutable $date, string $startTime): Booking
    {
        $worktime = new TrainerWorkTime();
        $worktime->setDate($date);
        $worktime->setStartTime(new DateTimeImmutable('09:00:00'));
        $worktime->setEndTime(new DateTimeImmutable('18:00:00'));

        $training = new Training();
        $training->setTrainerWorkTime($worktime);
        $training->setStartTime(new DateTimeImmutable($startTime));
        $training->setDurationMinutes(60);

        $booking = new Booking();
        $booking->setTraining($training);
        $booking->setClient(new Client());
        $booking->confirm();

        return $booking;
    }
}
