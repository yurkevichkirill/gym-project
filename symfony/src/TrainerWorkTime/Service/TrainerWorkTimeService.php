<?php

declare(strict_types=1);

namespace App\TrainerWorkTime\Service;

use App\Trainer\Repository\TrainerRepository;
use App\TrainerWorkTime\Repository\TrainerWorkTimeRepository;
use App\TrainerWorkTime\Service\TrainerWorkTimeServiceInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\CacheItem;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

readonly class TrainerWorkTimeService implements TrainerWorkTimeServiceInterface
{
    public function __construct(
        private TrainerRepository $trainerRepo,
        private TrainerWorkTimeRepository $trainerWorkTimeRepo,
        private TagAwareCacheInterface $gymCache
    )
    {}

    /**
     * @throws InvalidArgumentException
     */
    public function findBy(int $id, array $sort, ?\DateTimeImmutable $date): array
    {
        $cacheKey = $this->generateCacheKey($id, $sort, $date);

        return $this->gymCache->get($cacheKey, function (CacheItem $item) use ($id, $sort, $date): array
        {
            $item->tag(['trainer_worktimes_list']);


            $trainer = $this->trainerRepo->find($id);
            if(is_null($trainer)) {
                return [];
            }

            $criteria = ['trainer' => $trainer];
            if($date) {
                $criteria['date'] = $date;
            }

            return $this->trainerWorkTimeRepo->findBy($criteria, $sort);
        });
    }

    public function generateCacheKey(int $id, array $sort, ?\DateTimeImmutable $date): string
    {
        $params = [
            'trainerId' => $id,
            'sort' => $sort,
            'date' => $date
        ];

        return 'trainer_worktimes_' . md5(serialize($params));
    }
}
