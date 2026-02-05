<?php

declare(strict_types=1);

namespace App\Trainer\Service;

use App\Enum\DayOfWeekEnum;
use App\Trainer\Entity\Trainer;
use App\Trainer\Repository\TrainerRepository;
use App\TrainerWorkTime\Repository\TrainerWorkTimeRepository;
use App\Training\Repository\TrainingRepository;
use App\TrainingType\Repository\TrainingTypeRepository;
use DateInterval;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\CacheItem;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

readonly class TrainerService implements TrainerServiceInterface
{
    public function __construct(
        private TrainerWorkTimeRepository $trainerAvailabilityRepo,
        private TrainingRepository $trainingRepo,
        private TrainerRepository $trainerRepo,
        private TrainingTypeRepository $trainingTypeRepo,
        private TagAwareCacheInterface $gymCache
    )
    {}

    /**
     * @throws \DateMalformedIntervalStringException
     */
    public function getAvailable(Trainer $trainer, DayOfWeekEnum $dayOfWeek): array
    {
        $dayAvailabilities = $this->trainerAvailabilityRepo->findBy([
            'trainer' => $trainer,
            'day_of_week' => $dayOfWeek
        ])[0];
        $dayTrainings = $this->trainingRepo->findBy([
            'trainer' => $trainer,
            'day_of_week' => $dayOfWeek
        ]);

        $startTrainerTime = $dayAvailabilities->getStartTime();
        $endTrainerTime = $dayAvailabilities->getEndTime();
        usort($dayTrainings, fn ($training1, $training2) => $training1->getStartTime() <=> $training2->getStartTime());

        $available = [];
        $startPeriod = $startTrainerTime;
        foreach ($dayTrainings as $dayTraining) {
            $available[] = [
                "start" => $startPeriod,
                "end" => $dayTraining->getStartTime()
            ];
            $startPeriod = $dayTraining->getStartTime()->add(new DateInterval("PT" . $dayTraining->getDurationMinutes() . "M"));
        }
        $available[] = [
            "start" => $startPeriod,
            "end" => $endTrainerTime
        ];

        return $available;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function findBy(array $sort, ?int $trainingTypeId): array
    {
        $cacheKey = $this->generateCacheKey($sort, $trainingTypeId);

        return $this->gymCache->get($cacheKey, function (CacheItem $item) use($sort, $trainingTypeId): array
        {
            $item->tag('trainers_list');

            $criteria = [];
            if($trainingTypeId) {
                $trainingType = $this->trainingTypeRepo->find($trainingTypeId);
                $criteria['trainingType'] = $trainingType;
            }

            return $this->trainerRepo->findBy($criteria, $sort);
        });
    }

    public function generateCacheKey(array $sort, ?int $trainingTypeId): string
    {
        $params = [
            'sort' => $sort,
            'trainingTypeId' => $trainingTypeId
        ];

        return 'trainers_' . md5(serialize($params));
    }
}
