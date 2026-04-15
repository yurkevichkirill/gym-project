<?php

declare(strict_types=1);

namespace App\Trainer\Query;

use App\Trainer\DTO\GetTrainers;
use App\Trainer\DTO\TrainerFilter;
use App\Trainer\Repository\TrainerRepository;
use Doctrine\ORM\QueryBuilder;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\CacheItem;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final readonly class TrainersQuery
{
    private const array SORT_MAP = [
        'trainingTypeId' => 'type.id',
    ];

    public function __construct(
        private TrainerRepository $repo,
        private TagAwareCacheInterface $cache
    ) {}

    /**
     * @throws InvalidArgumentException
     */
    public function handle(GetTrainers $dto): array
    {
        $cacheKey = $this->generateCacheKey($dto);

        return $this->cache->get($cacheKey, function (CacheItem $item) use ($dto) {
            $item->expiresAfter(3600);

            $qb = $this->createQuery($dto->filter);

            foreach ($dto->sort as $field => $order) {
                $mapped = self::SORT_MAP[$field] ?? "t.$field";
                $qb->addOrderBy($mapped, $order);
            }

            $qb->setFirstResult(($dto->page - 1) * $dto->limit)
                ->setMaxResults($dto->limit);

            $item->tag(['trainers_list']);

            return $qb->getQuery()->getResult();
        });
    }

    public function getTotal(TrainerFilter $filter): int
    {
        return (int) $this->createQuery($filter)
            ->select('COUNT(t.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function createQuery(TrainerFilter $filter): QueryBuilder
    {
        $qb = $this->repo->createQueryBuilder('t')
            ->leftJoin('t.trainingType', 'type')
            ->addSelect('type');

        if ($filter->minPricePerHour !== null) {
            $qb->andWhere('t.pricePerHour >= :minPricePerHour')
                ->setParameter('minPricePerHour', $filter->minPricePerHour);
        }

        if ($filter->maxPricePerHour !== null) {
            $qb->andWhere('t.pricePerHour <= :maxPricePerHour')
                ->setParameter('maxPricePerHour', $filter->maxPricePerHour);
        }

        if ($filter->trainingType !== null) {
            $qb->andWhere('t.trainingType = :trainingType')
                ->setParameter('trainingType', $filter->trainingType);
        }

        return $qb;
    }

    private function generateCacheKey(GetTrainers $dto): string
    {
        return 'trainers_' . md5(serialize([
                'sort' => $dto->sort,
                'page' => $dto->page,
                'limit' => $dto->limit,
                'minPricePerHour' => $dto->filter->minPricePerHour,
                'maxPricePerHour' => $dto->filter->maxPricePerHour,
                'trainingTypeId' => $dto->filter->trainingType?->getId(),
            ]));
    }
}
