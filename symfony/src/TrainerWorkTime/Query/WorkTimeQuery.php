<?php

declare(strict_types=1);

namespace App\TrainerWorkTime\Query;

use App\TrainerWorkTime\DTO\GetTrainerWorkTime;
use App\TrainerWorkTime\DTO\WorkTimeFilter;
use App\TrainerWorkTime\Repository\TrainerWorkTimeRepository;
use Doctrine\ORM\QueryBuilder;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\ItemInterface;
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

        return $this->gymCache->get($cacheKey, function (ItemInterface $item, bool $save) use ($dto): array {

            $item->expiresAfter(3600);

            $qb = $this->createQuery($dto->filter);

            $offset = ($dto->page - 1) * $dto->limit;

            foreach ($dto->sort as $field => $order) {
                $qb->addOrderBy("w.$field", $order);
            }

            $qb->setFirstResult($offset)
                ->setMaxResults($dto->limit);

            if ($dto->filter->trainer) {
                $item->tag(['trainer_worktimes_list_' . $dto->filter->trainer->getId()]);
            } else {
                $item->tag(["trainer_worktimes_list_all"]);
            }

            return $qb->getQuery()->getResult();
        });
    }

    public function getTotal(WorkTimeFilter $filter): int
    {
        return (int) $this->createQuery($filter)
            ->select('COUNT(w.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function createQuery(WorkTimeFilter $filter): QueryBuilder
    {
        $qb = $this->worktimeRepo->createQueryBuilder('w')
            ->leftJoin('w.trainer', 'trainer')
            ->addSelect('trainer')
            ->leftJoin('w.trainings', 'tr')
            ->addSelect('tr')
            ->leftJoin('trainer.trainingType', 'type')
            ->addSelect('type');

        if ($filter->trainer !== null) {
            $qb->andWhere('trainer = :trainer')
                ->setParameter('trainer', $filter->trainer);
        }

        if ($filter->date !== null) {
            $qb->andWhere('w.date = :date')
                ->setParameter('date', $filter->date);
        }

        return $qb;
    }

    private function generateCacheKey(GetTrainerWorkTime $query): string
    {
        $f = $query->filter;

        $params = [
            'sort' => $query->sort,
            'page' => $query->page,
            'limit' => $query->limit,
            'trainerId' => $f->trainer?->getId(),
            'date' => $f->date?->format('Y-m-d'),
        ];

        return 'trainer_worktime_' . md5(json_encode($params));
    }
}
