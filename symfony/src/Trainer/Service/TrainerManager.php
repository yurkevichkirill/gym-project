<?php

declare(strict_types=1);

namespace App\Trainer\Service;

use App\Trainer\DTO\UpdateTrainerRequest;
use App\Trainer\Entity\Trainer;
use App\Trainer\Repository\TrainerRepository;
use App\TrainerWorkTime\Repository\TrainerWorkTimeRepository;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;

class TrainerManager
{
    const int MIN_DURATION = 30;
    const int TRAINER_PRICE_DIVIDER = 2;
    public function __construct(
        private TrainerRepository $trainerRepo,
        private TrainerWorkTimeRepository $worktimeRepo,
    )
    {}

    public function update(Trainer $trainer, UpdateTrainerRequest $requestDto): Trainer
    {
        if($requestDto->phone) {
            $trainer->setPhone($requestDto->phone);
        }
        if($requestDto->pricePerHour) {
            $trainer->setPricePerHour($requestDto->pricePerHour);
        }

        $this->trainerRepo->save();

        return $trainer;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function softDelete(Trainer $trainer): void
    {
        foreach ($trainer->getTrainerWorkTime() as $worktime) {
            $this->worktimeRepo->remove($worktime);
        }

        $this->trainerRepo->remove($trainer);
    }

    public function countPrice(Trainer $trainer, int $durationMinutes): float
    {
        $pricePerHour = (float) $trainer->getPricePerHour();

        return $durationMinutes / self::MIN_DURATION * $pricePerHour / self::TRAINER_PRICE_DIVIDER;
    }
}
