<?php

declare(strict_types=1);

namespace App\Tests\Booking\DTO;

use App\Booking\DTO\BookingRequestDTO;
use App\Booking\DTO\GetBookingsRequestDTO;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validation;

final class BookingRequestDTOTest extends TestCase
{
    public function testBookingDurationCannotExceedOneDay(): void
    {
        $dto = new BookingRequestDTO(
            durationMinutes: 1470,
            startTime: '10:00:00',
            trainerId: 1,
            date: '2026-07-01',
        );

        $violations = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator()
            ->validate($dto);

        self::assertTrue($this->containsViolationFor($violations, 'durationMinutes'));
    }

    public function testBookingsPageSizeCannotExceedOneHundred(): void
    {
        $dto = new GetBookingsRequestDTO(limit: 101);

        $violations = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator()
            ->validate($dto);

        self::assertTrue($this->containsViolationFor($violations, 'limit'));
    }

    /**
     * @param iterable<ConstraintViolationInterface> $violations
     */
    private function containsViolationFor(iterable $violations, string $propertyPath): bool
    {
        foreach ($violations as $violation) {
            if ($violation->getPropertyPath() === $propertyPath) {
                return true;
            }
        }

        return false;
    }
}
