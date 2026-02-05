<?php

declare(strict_types=1);

namespace App\Training\Service;

use App\Enum\DayOfWeekEnum;
use App\Trainer\Repository\TrainerRepository;
use App\TrainerWorkTime\Entity\TrainerWorkTime;
use App\TrainerWorkTime\Repository\TrainerWorkTimeRepository;
use App\Training\Repository\TrainingRepository;
use App\Training\Service\TrainingServiceInterface;
use DateTimeImmutable;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

readonly class TrainingService implements TrainingServiceInterface
{
    public function __construct(
        private TrainerRepository  $trainerRepo,
        private TrainerWorkTimeRepository $trainerWorkTimeRepo,
        private TrainingRepository $trainingRepo,
        private TagAwareCacheInterface $gymCache
    )
    {}

    /**
     * @throws InvalidArgumentException
     */
    public function findBy(
        int $trainerId,
        array $sort,
        ?DateTimeImmutable $date = null
    ): array
    {
        $cacheKey = $this->generateCacheKey($trainerId, $sort, $date);

        return $this->gymCache->get($cacheKey, function (CacheItem $item) use ($trainerId, $sort, $date): array
        {
            $item->tag(['trainings_list']);

            $trainer = $this->trainerRepo->find($trainerId);
            if(is_null($trainer)) {
                return [];
            }
            $criteria = ['trainer' => $trainer];
            if($date) {
                $criteria['date'] = $date;
            }

            $trainerWorkTimes = $this->trainerWorkTimeRepo->findBy($criteria);

            $trainings = [];
            foreach ($trainerWorkTimes as $worktime) {
                $dayTrainings = $this->trainingRepo->findBy(['trainerWorkTime' => $worktime], $sort);
                $trainings = array_merge($trainings, $dayTrainings);
            }

            return $trainings;
        });
    }

    public function generateCacheKey(int $trainerId, array $sort, ?DateTimeImmutable $date): string
    {
        $params = [
            'trainerId' => $trainerId,
            'sort' => $sort,
            'date' => $date
        ];

        return 'trainings_' . md5(serialize($params));
    }
}
