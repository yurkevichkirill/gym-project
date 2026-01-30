<?php

declare(strict_types=1);

namespace App\Trainer\Service;

use App\Enum\DayOfWeekEnum;
use App\Trainer\Entity\Trainer;
use Doctrine\ORM\EntityManagerInterface;

interface TrainerServiceInterface
{
    public function getAvailable(Trainer $trainer, DayOfWeekEnum $dayOfWeek, EntityManagerInterface $em): array;
    public function getScheduled(Trainer $trainer, DayOfWeekEnum $dayOfWeek): array;
}
