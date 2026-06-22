<?php

declare(strict_types=1);

namespace App\Tests\TrainerWorkTime\Repository;

use App\Trainer\Entity\Trainer;
use App\TrainerWorkTime\Entity\TrainerWorkTime;
use App\TrainerWorkTime\Repository\TrainerWorkTimeRepository;
use App\Training\Entity\Training;
use App\Training\Repository\TrainingRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class TrainerWorkTimeRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private TrainerWorkTimeRepository $worktimeRepository;
    private TrainingRepository $trainingRepository;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = self::getContainer();
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->worktimeRepository = $container->get(TrainerWorkTimeRepository::class);
        $this->trainingRepository = $container->get(TrainingRepository::class);
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

    public function testFindForUpdateRefreshesAlreadyManagedEntity(): void
    {
        $worktime = $this->persistWorktime();
        $worktimeId = $worktime->getId();
        self::assertIsInt($worktimeId);
        self::assertSame("09:00:00", $worktime->getStartTime()->format("H:i:s"));

        $this->entityManager->getConnection()->executeStatement(
            "UPDATE trainer_work_time SET start_time = :startTime WHERE id = :id",
            ["startTime" => "08:30:00", "id" => $worktimeId],
        );

        $lockedWorktime = $this->worktimeRepository->findForUpdate($worktimeId);

        self::assertSame($worktime, $lockedWorktime);
        self::assertSame("08:30:00", $lockedWorktime?->getStartTime()->format("H:i:s"));
    }

    public function testExistsForWorktimeDetectsAnyTrainingHistory(): void
    {
        $worktime = $this->persistWorktime();

        self::assertFalse($this->trainingRepository->existsForWorktime($worktime));

        $training = new Training();
        $training->setTrainerWorkTime($worktime);
        $training->setStartTime(new DateTimeImmutable("10:00:00"));
        $training->setDurationMinutes(60);
        $training->setIsBusy(false);

        $this->entityManager->persist($training);
        $this->entityManager->flush();

        self::assertTrue($this->trainingRepository->existsForWorktime($worktime));
    }

    private function persistWorktime(): TrainerWorkTime
    {
        $trainer = new Trainer();
        $suffix = bin2hex(random_bytes(6));
        $trainer->setEmail("repo_trainer_{$suffix}@example.com");
        $trainer->setFirstName("Repo");
        $trainer->setLastName("Trainer");
        $trainer->setPhone("+37533" . random_int(1000000, 9999999));
        $trainer->setPassword("password");
        $trainer->setPricePerHour(1000);
        $trainer->setIsActive(true);

        $worktime = new TrainerWorkTime();
        $worktime->setTrainer($trainer);
        $worktime->setDate(new DateTimeImmutable("2026-08-20"));
        $worktime->setStartTime(new DateTimeImmutable("09:00:00"));
        $worktime->setEndTime(new DateTimeImmutable("12:00:00"));

        $this->entityManager->persist($trainer);
        $this->entityManager->persist($worktime);
        $this->entityManager->flush();

        return $worktime;
    }
}
