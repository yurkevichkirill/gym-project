<?php

declare(strict_types=1);

namespace App\Training\Query;

use App\Trainer\Repository\TrainerRepository;
use App\Training\DTO\GetTrainings;
use App\Training\Repository\TrainingRepository;
use Doctrine\ORM\QueryBuilder;
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
            $qb = $this->createQuery($dto->filter);

            $offset = ($dto->page - 1) * $dto->limit;

            foreach ($dto->sort as $alias => $order) {
                $field = self::SORT_MAP[$alias] ?? "t.$alias";
                $qb->addOrderBy("$field", $order);
            }
            $qb->setFirstResult($offset)
                ->setMaxResults($dto->limit);

            $item->tag(['trainings_list_' . $dto->filter['trainer']->getId()]);

            return $qb->getQuery()->getResult();
        });
    }

    public function getTotal(array $filter): int
    {
        return $this->createQuery($filter, true)->select("COUNT(t.id)")->getQuery()->getSingleScalarResult();
    }

    private function createQuery(array $filter, bool $isCount = false): QueryBuilder
    {
        $qb = $this->trainingRepo->createQueryBuilder('t');

        if(!$isCount) {
            $qb->addSelect('b', 'w');
        }

        $qb->innerJoin('t.booking', 'b')
            ->innerJoin('t.trainerWorkTime', 'w')
            ->andWhere('w.trainer = :trainer')
            ->setParameter('trainer', $filter['trainer']);

        if(isset($filter['client'])) {
            $qb->andWhere('b.client = :client')
                ->setParameter('client', $filter['client']);
        }

        if(isset($filter['status'])) {
            $qb->andWhere('b.status = :status')
                ->setParameter('status', $filter['status']);
        }

        if(isset($filter['date'])) {
            $qb->andWhere('w.date = :date')
                ->setParameter('date', $filter['date']);
        }

        if(isset($filter['startTime'])) {
            $qb->andWhere('t.startTime = :startTime')
                ->setParameter('startTime', $filter['startTime']);
        }

        if(isset($filter['durationMinutes'])) {
            $qb->andWhere('t.durationMinutes = :durationMinutes')
                ->setParameter('durationMinutes', $filter['durationMinutes']);
        }

        return $qb;
    }

    private function generateCacheKey(GetTrainings $query): string
    {
        $params = [
            'trainer' => $query->filter['trainer'],
            'sort' => $query->sort,
            'page' => $query->page,
            'limit' => $query->limit,
        ];

        if(isset($query->filter['status'])) {
            $params['status'] = $query->filter['status'];
        }
        if(isset($query->filter['client'])) {
            $params['client'] = $query->filter['client'];
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
