<?php

declare(strict_types=1);

namespace App\Tests\Training\Entity;

use App\Training\Entity\Training;
use App\Training\Exception\TrainingCrossesMidnightException;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class TrainingTest extends TestCase
{
    public function testTrainingCannotCrossMidnightWhenStartTimeIsChanged(): void
    {
        $training = new Training();
        $training->setDurationMinutes(60);

        $this->expectException(TrainingCrossesMidnightException::class);

        $training->setStartTime(new DateTimeImmutable('23:30:00'));
    }

    public function testTrainingCannotCrossMidnightWhenDurationIsChanged(): void
    {
        $training = new Training();
        $training->setStartTime(new DateTimeImmutable('22:30:00'));

        $this->expectException(TrainingCrossesMidnightException::class);

        $training->setDurationMinutes(120);
    }

    public function testTrainingCanEndBeforeMidnight(): void
    {
        $training = new Training();
        $training->setDurationMinutes(60);
        $training->setStartTime(new DateTimeImmutable('22:30:00'));

        self::assertSame('22:30:00', $training->getStartTime()->format('H:i:s'));
        self::assertSame(60, $training->getDurationMinutes());
    }
}
