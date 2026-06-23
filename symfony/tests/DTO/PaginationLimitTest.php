<?php

declare(strict_types=1);

namespace App\Tests\DTO;

use App\Booking\DTO\GetBookingsRequestDTO;
use App\Client\DTO\GetClientsRequestDTO;
use App\Membership\DTO\GetMembershipsRequestDTO;
use App\MembershipPlan\DTO\GetMembershipPlansRequestDTO;
use App\Payment\DTO\GetPaymentsRequestDTO;
use App\Trainer\DTO\GetTrainersRequestAdminDTO;
use App\Trainer\DTO\GetTrainersRequestDTO;
use App\TrainerWorkTime\DTO\GetWorktimesRequestDTO;
use App\Training\DTO\GetTrainingsRequestDTO;
use App\TrainingType\DTO\GetTrainingTypesRequestDTO;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validation;

final class PaginationLimitTest extends TestCase
{
    /**
     * @param object $dto
     */
    #[DataProvider('oversizedListRequestProvider')]
    public function testListRequestRejectsMoreThanOneHundredItems(object $dto): void
    {
        $violations = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator()
            ->validate($dto);

        foreach ($violations as $violation) {
            if ($violation->getPropertyPath() === 'limit') {
                self::assertInstanceOf(ConstraintViolationInterface::class, $violation);

                return;
            }
        }

        self::fail(sprintf(
            '%s must reject limit values greater than 100',
            $dto::class,
        ));
    }

    /**
     * @return Generator<string, array{object}>
     */
    public static function oversizedListRequestProvider(): Generator
    {
        yield 'bookings' => [new GetBookingsRequestDTO(limit: 101)];
        yield 'clients' => [new GetClientsRequestDTO(limit: 101)];
        yield 'memberships' => [new GetMembershipsRequestDTO(limit: 101)];
        yield 'membership plans' => [new GetMembershipPlansRequestDTO(limit: 101)];
        yield 'payments' => [new GetPaymentsRequestDTO(limit: 101)];
        yield 'admin trainers' => [new GetTrainersRequestAdminDTO(limit: 101)];
        yield 'public trainers' => [new GetTrainersRequestDTO(limit: 101)];
        yield 'worktimes' => [new GetWorktimesRequestDTO(limit: 101)];
        yield 'trainings' => [new GetTrainingsRequestDTO(limit: 101)];
        yield 'training types' => [new GetTrainingTypesRequestDTO(limit: 101)];
    }
}
