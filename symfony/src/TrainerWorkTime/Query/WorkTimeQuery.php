<?php

declare(strict_types=1);

namespace App\TrainerWorkTime\Query;

use App\TrainerWorkTime\DTO\GetTrainerWorkTime;
use App\TrainerWorkTime\Repository\TrainerWorkTimeRepository;
use Doctrine\ORM\QueryBuilder;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\CacheItem;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final readonly class WorkTimeQuery
{
    public function __construct(
        private TrainerWorkTimeRepository $worktimeRepo,
        private TagAwareCacheInterface $gymCache
    )
    {}

    /**
     * @throws InvalidArgumentException
     */
    public function handle(GetTrainerWorkTime $dto): array
    {
        $cacheKey = $this->generateCacheKey($dto);

        return $this->gymCache->get($cacheKey, function (CacheItem $item) use ($dto): array
        {
            $item->expiresAfter(3600);

            $qb = $this->createQuery($dto->filter);

            $offset = ($dto->page - 1) * $dto->limit;

            foreach ($dto->sort as $field => $order) {
                $qb->addOrderBy("w.$field", $order);
            }
            $qb->setFirstResult($offset)
                ->setMaxResults($dto->limit);

            if (isset($dto->filter['client'])) {
                $item->tag(['trainer_worktimes_list_' . $dto->filter['trainer']->getId()]);
            } else {
                $item->tag(["trainer_worktimes_list_all"]);
            }

            return $qb->getQuery()->getResult();
        });
    }

    public function getTotal(array $filter): int
    {
        return $this->createQuery($filter)->select("COUNT(w.id)")->getQuery()->getSingleScalarResult();
    }

    private function createQuery(array $filter): QueryBuilder
    {
        $qb = $this->worktimeRepo->createQueryBuilder('w')
            ->leftJoin('w.trainer', 'trainer')
            ->addSelect('trainer')
            ->leftJoin('w.trainings', 'tr')
            ->addSelect('tr')
            ->leftJoin('trainer.trainingType', 'type')
            ->addSelect('type');

        if (isset($filter['trainer'])) {
            $qb->andWhere('trainer = :trainer')
                ->setParameter('trainer', $filter['trainer']);
        }

        if (isset($filter['date'])) {
            $qb->andWhere('w.date = :date')
                ->setParameter('date', $filter['date']);
        }

        return $qb;
    }

    private function generateCacheKey(GetTrainerWorkTime $query): string
    {
        $params = [
            'sort' => $query->sort,
            'page' => $query->page,
            'limit' => $query->limit,
        ];

        if (isset($query->filter['trainer'])) {
            $params['trainer'] = $query->filter['trainer'];
        }

        if (isset($query->filter['date'])) {
            $params['date'] = $query->filter['date'];
        }

        return 'trainer_worktime_' . md5(serialize($params));
    }
}
