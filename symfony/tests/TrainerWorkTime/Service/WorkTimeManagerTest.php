<?php

declare(strict_types=1);

namespace App\Tests\TrainerWorkTime\Service;

use App\Booking\Exception\DateTimeAlreadyTakenException;
use App\Trainer\Entity\Trainer;
use App\TrainerWorkTime\DTO\UpdateWorkTimeRequestDTO;
use App\TrainerWorkTime\Entity\TrainerWorkTime;
use App\TrainerWorkTime\Exception\WorktimeHasActiveTrainingsException;
use App\TrainerWorkTime\Service\WorkTimeManager;
use App\Training\Entity\Training;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\WithHttpStatus;

final class WorkTimeManagerTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private WorkTimeManager $manager;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = self::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->manager = $container->get(WorkTimeManager::class);
        $this->entityManager->getConnection()->beginTransaction();
    }

    protected function tearDown(): void
    {
        if (isset($this->entityManager) && $this->entityManager->getConnection()->isTransactionActive()) {
            $this->entityManager->getConnection()->rollBack();
        }

        if (isset($this->entityManager)) {
            $this->entityManager->close();
        }

        parent::tearDown();
    }

    public function testUpdateIgnoresTrainingWithIsBusyFalse(): void
    {
        $worktime = $this->persistWorktime();
        $this->persistTraining($worktime, "09:00:00", 60, false);

        $updated = $this->manager->update(
            $worktime,
            new UpdateWorkTimeRequestDTO("10:00:00", "11:00:00"),
            true,
        );

        self::assertSame("10:00:00", $updated->getStartTime()->format("H:i:s"));
        self::assertSame("11:00:00", $updated->getEndTime()->format("H:i:s"));
    }

    public function testUpdateRejectsNewStartAfterActiveTrainingStart(): void
    {
        $worktime = $this->persistWorktime();
        $this->persistTraining($worktime, "10:00:00", 60, true);

        $this->expectException(DateTimeAlreadyTakenException::class);

        $this->manager->update(
            $worktime,
            new UpdateWorkTimeRequestDTO("10:00:01", "12:00:00"),
            true,
        );
    }

    public function testUpdateRejectsNewEndBeforeActiveTrainingEnd(): void
    {
        $worktime = $this->persistWorktime();
        $this->persistTraining($worktime, "10:00:00", 60, true);

        $this->expectException(DateTimeAlreadyTakenException::class);

        $this->manager->update(
            $worktime,
            new UpdateWorkTimeRequestDTO("09:00:00", "10:59:59"),
            true,
        );
    }

    public function testUpdateAcceptsBoundariesExactlyEqualToActiveTrainingBoundaries(): void
    {
        $worktime = $this->persistWorktime();
        $this->persistTraining($worktime, "10:00:00", 60, true);

        $updated = $this->manager->update(
            $worktime,
            new UpdateWorkTimeRequestDTO("10:00:00", "11:00:00"),
            true,
        );

        self::assertSame("10:00:00", $updated->getStartTime()->format("H:i:s"));
        self::assertSame("11:00:00", $updated->getEndTime()->format("H:i:s"));
    }

    public function testUpdateAllowsShrinkWhenThereAreNoActiveTrainings(): void
    {
        $worktime = $this->persistWorktime();

        $updated = $this->manager->update(
            $worktime,
            new UpdateWorkTimeRequestDTO("10:00:00", "11:00:00"),
            true,
        );

        self::assertSame("10:00:00", $updated->getStartTime()->format("H:i:s"));
        self::assertSame("11:00:00", $updated->getEndTime()->format("H:i:s"));
    }

    #[DataProvider("trainingBusyStates")]
    public function testRemoveRejectsWorktimeContainingAnyTrainingHistory(bool $isBusy): void
    {
        $worktime = $this->persistWorktime();
        $this->persistTraining($worktime, "10:00:00", 60, $isBusy);

        $this->expectException(WorktimeHasActiveTrainingsException::class);

        $this->manager->remove($worktime);
    }

    /**
     * @return iterable<string, array{bool}>
     */
    public static function trainingBusyStates(): iterable
    {
        yield "historical not busy training" => [false];
        yield "active busy training" => [true];
    }

    public function testRemoveDeletesWorktimeWithoutTraining(): void
    {
        $worktime = $this->persistWorktime();
        $worktimeId = $worktime->getId();
        self::assertIsInt($worktimeId);

        $this->manager->remove($worktime);

        $this->entityManager->clear();
        self::assertNull($this->entityManager->find(TrainerWorkTime::class, $worktimeId));
    }

    public function testWorktimeHasActiveTrainingsExceptionMapsToHttp409(): void
    {
        $reflection = new \ReflectionClass(WorktimeHasActiveTrainingsException::class);
        $attributes = $reflection->getAttributes(WithHttpStatus::class);

        self::assertCount(1, $attributes);
        self::assertSame(Response::HTTP_CONFLICT, $attributes[0]->newInstance()->statusCode);
    }

    private function persistWorktime(): TrainerWorkTime
    {
        $trainer = new Trainer();
        $suffix = bin2hex(random_bytes(6));
        $trainer->setEmail("trainer_{$suffix}@example.com");
        $trainer->setFirstName("Test");
        $trainer->setLastName("Trainer");
        $trainer->setPhone("+37529" . random_int(1000000, 9999999));
        $trainer->setPassword("password");
        $trainer->setPricePerHour(1000);
        $trainer->setIsActive(true);

        $worktime = new TrainerWorkTime();
        $worktime->setTrainer($trainer);
        $worktime->setDate(new DateTimeImmutable("2026-07-20"));
        $worktime->setStartTime(new DateTimeImmutable("09:00:00"));
        $worktime->setEndTime(new DateTimeImmutable("12:00:00"));

        $this->entityManager->persist($trainer);
        $this->entityManager->persist($worktime);
        $this->entityManager->flush();

        return $worktime;
    }

    private function persistTraining(
        TrainerWorkTime $worktime,
        string $startTime,
        int $durationMinutes,
        bool $isBusy,
    ): Training {
        $training = new Training();
        $training->setTrainerWorkTime($worktime);
        $training->setStartTime(new DateTimeImmutable($startTime));
        $training->setDurationMinutes($durationMinutes);
        $training->setIsBusy($isBusy);

        $this->entityManager->persist($training);
        $this->entityManager->flush();

        return $training;
    }
}
