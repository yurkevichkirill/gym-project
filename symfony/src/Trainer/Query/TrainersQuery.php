<?php

declare(strict_types=1);

namespace App\Trainer\Query;

use App\Trainer\DTO\GetTypesTrainers;
use App\Trainer\Repository\TrainerRepository;
use Doctrine\ORM\QueryBuilder;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\CacheItem;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final readonly class TrainersQuery
{
    private const array SORT_MAP = [
        'trainingTypeId' => 'type.id'
    ];

    public function __construct(
        private TrainerRepository      $trainerRepo,
        private TagAwareCacheInterface $gymCache
    )
    {}

    /**
     * @throws InvalidArgumentException
     */
    public function handle(GetTypesTrainers $dto): array
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

            $item->tag(['trainers_list']);

            return $qb->getQuery()->getResult();
        });
    }

    public function getTotal(array $filter): int
    {
        return $this->createQuery($filter)->select("COUNT(t.id)")->getQuery()->getSingleScalarResult();
    }

    private function createQuery(array $filter): QueryBuilder
    {
        $qb = $this->trainerRepo->createQueryBuilder('t')
            ->leftJoin('t.trainingType', 'type');

        if(isset($filter['minPrice'])) {
            $qb->andWhere('t.pricePerHour >= :minPrice')
                ->setParameter('minPrice', $filter['minPrice']);
        }
        if(isset($filter['maxPrice'])) {
            $qb->andWhere('t.pricePerHour <= :maxPrice')
                ->setParameter('maxPrice', $filter['maxPrice']);
        }
        if(isset($filter['trainingType'])) {
            $qb->andWhere('t.trainingType = :trainingType')
                ->setParameter('trainingType', $filter['trainingType']);
        }

        return $qb;
    }

    private function generateCacheKey(GetTypesTrainers $query): string
    {
        $params = [
            'sort' => $query->sort,
            'page' => $query->page,
            'limit' => $query->limit,
        ];
        if(isset($query->filter['minPrice'])) {
            $params['minPrice'] = $query->filter['minPrice'];
        }
        if(isset($query->filter['maxPrice'])) {
            $params['maxPrice'] = $query->filter['maxPrice'];
        }
        if(isset($query->filter['trainingType'])) {
            $params['trainingType'] = $query->filter['trainingType'];
        }

        return 'trainers_' . md5(serialize($params));
    }
}
