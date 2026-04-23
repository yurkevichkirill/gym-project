<?php

declare(strict_types=1);

namespace App\Training\Query;

use App\Training\DTO\GetTrainings;
use App\Training\DTO\TrainingFilter;
use App\Training\Repository\TrainingRepository;
use Doctrine\ORM\QueryBuilder;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\ItemInterface;
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
        private readonly TrainingRepository $trainingRepo,
        private readonly TagAwareCacheInterface $gymCache
    )
    {}

    /**
     * @throws InvalidArgumentException
     */
    public function handle(GetTrainings $dto): array
    {
        $cacheKey = $this->generateCacheKey($dto);

        return $this->gymCache->get($cacheKey, function (ItemInterface $item, bool $save) use ($dto): array {

            $item->expiresAfter(3600);

            $qb = $this->createQuery($dto->filter);

            $offset = ($dto->page - 1) * $dto->limit;

            foreach ($dto->sort as $alias => $order) {
                $field = self::SORT_MAP[$alias] ?? "t.$alias";
                $qb->addOrderBy($field, $order);
            }

            $qb->setFirstResult($offset)
                ->setMaxResults($dto->limit);

            if ($dto->filter->trainer !== null) {
                $item->tag(['trainings_list_' . $dto->filter->trainer->getId()]);
            } else {
                $item->tag(['trainings_list_all']);
            }

            return $qb->getQuery()->getResult();
        });
    }

    public function getTotal(TrainingFilter $filter): int
    {
        return (int) $this->createQuery($filter, true)
            ->select('COUNT(t.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function createQuery(TrainingFilter $filter, bool $isCount = false): QueryBuilder
    {
        $qb = $this->trainingRepo->createQueryBuilder('t')
            ->innerJoin('t.booking', 'b')
            ->innerJoin('t.trainerWorkTime', 'w')
            ->innerJoin('b.payment', 'p');

        if (!$isCount) {
            $qb->addSelect('b', 'w', 'p');
        }

        if ($filter->trainer) {
            $qb->andWhere('w.trainer = :trainer')
                ->setParameter('trainer', $filter->trainer);
        }

        if ($filter->client) {
            $qb->andWhere('b.client = :client')
                ->setParameter('client', $filter->client);
        }

        if ($filter->status !== null) {
            $qb->andWhere('b.status = :status')
                ->setParameter('status', $filter->status);
        }

        if ($filter->date !== null) {
            $qb->andWhere('w.date = :date')
                ->setParameter('date', $filter->date);
        }

        if ($filter->startTime !== null) {
            $qb->andWhere('t.startTime = :startTime')
                ->setParameter('startTime', $filter->startTime);
        }

        if ($filter->durationMinutes !== null) {
            $qb->andWhere('t.durationMinutes = :durationMinutes')
                ->setParameter('durationMinutes', $filter->durationMinutes);
        }

        return $qb;
    }

    private function generateCacheKey(GetTrainings $query): string
    {
        $f = $query->filter;

        $params = [
            'sort' => $query->sort,
            'page' => $query->page,
            'limit' => $query->limit,
            'trainerId' => $f->trainer?->getId(),
            'clientId' => $f->client?->getId(),
            'status' => $f->status,
            'date' => $f->date?->format('Y-m-d'),
            'startTime' => $f->startTime?->format('H:i:s'),
            'durationMinutes' => $f->durationMinutes,
        ];

        return 'trainings_' . md5(json_encode($params));
    }
}
