<?php

declare(strict_types=1);

namespace App\Training\Query;

use App\Trainer\Repository\TrainerRepository;
use App\Training\DTO\GetTrainings;
use App\Training\Repository\TrainingRepository;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\CacheItem;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class TrainingsQuery
{
    private const array SORT_MAP = [
        'clientId' => 'c.id',
        'date' => 'w.date',
        'status' => 'b.status',
        'bookedAt' => 'b.bookedAt',
    ];

    public function __construct(
        private TrainingRepository     $trainingRepo,
        private TrainerRepository      $trainerRepo,
        private TagAwareCacheInterface $gymCache
    )
    {}

    /**
     * @throws InvalidArgumentException
     */
    public function handle(GetTrainings $dto): array
    {
        $cacheKey = $this->generateCacheKey($dto);

        return $this->gymCache->get($cacheKey, function (CacheItem $item) use ($dto): array
        {
            $trainer = $this->trainerRepo->find($dto->filter['trainerId']);

            $qb = $this->trainingRepo->createQueryBuilder('t')
                ->addSelect('b', 'w', 'c')
                ->innerJoin('t.booking', 'b')
                ->innerJoin('t.trainerWorkTime', 'w')
                ->innerJoin('b.client', 'c')
                ->andWhere('w.trainer = :trainer')
                ->setParameter('trainer', $trainer);

            if(isset($dto->filter['clientId'])) {
                $qb->andWhere('c.id = :clientId')
                    ->setParameter('clientId', $dto->filter['clientId']);
            }

            if(isset($dto->filter['status'])) {
                $qb->andWhere('b.status = :status')
                    ->setParameter('status', $dto->filter['status']);
            }

            if(isset($dto->filter['date'])) {
                $qb->andWhere('w.date = :date')
                    ->setParameter('date', $dto->filter['date']);
            }

            if(isset($dto->filter['startTime'])) {
                $qb->andWhere('t.startTime = :startTime')
                    ->setParameter('startTime', $dto->filter['startTime']);
            }

            if(isset($dto->filter['durationMinutes'])) {
                $qb->andWhere('t.durationMinutes = :durationMinutes')
                    ->setParameter('durationMinutes', $dto->filter['durationMinutes']);
            }

            $offset = ($dto->page - 1) * $dto->limit;

            foreach ($dto->sort as $alias => $order) {
                $field = self::SORT_MAP[$alias] ?? "t.$alias";
                $qb->addOrderBy("$field", $order);
            }
            $qb->setFirstResult($offset)
                ->setMaxResults($dto->limit);

            $item->tag(['trainings_list']);

            return $qb->getQuery()->getResult();
        });
    }

    private function generateCacheKey(GetTrainings $query): string
    {
        $params = [
            'trainerId' => $query->filter['trainerId'],
            'sort' => $query->sort,
            'page' => $query->page,
            'limit' => $query->limit,
        ];

        if(isset($query->filter['status'])) {
            $params['status'] = $query->filter['status'];
        }
        if(isset($query->filter['clientId'])) {
            $params['clientId'] = $query->filter['clientId'];
        }
        if(isset($query->filter['date'])) {
            $params['date'] = $query->filter['date'];
        }
        if(isset($query->filter['startTime'])) {
            $params['startTime'] = $query->filter['startTime'];
        }
        if(isset($query->filter['durationMinutes'])) {
            $params['durationMinutes'] = $query->filter['durationMinutes'];
        }

        return 'trainings_' . md5(serialize($params));
    }
}
