<?php

declare(strict_types=1);

namespace App\Trainer\Query;

use App\Trainer\DTO\GetTypesTrainers;
use App\Trainer\Repository\TrainerRepository;
use App\TrainingType\Repository\TrainingTypeRepository;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\CacheItem;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class TrainersQuery
{
    private const array SORT_MAP = [
        'trainingTypeId' => 'type.id'
    ];

    public function __construct(
        private TrainerRepository      $trainerRepo,
        private TrainingTypeRepository $trainingTypeRepo,
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
            $qb = $this->trainerRepo->createQueryBuilder('t')
                ->leftJoin('t.trainingType', 'type');

            if(isset($dto->filter['trainingTypeId'])) {
                $qb->andWhere('t.trainingType = :trainingType')
                    ->setParameter('trainingType', $this->trainingTypeRepo->find($dto->filter['trainingTypeId']));
            }

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

    private function generateCacheKey(GetTypesTrainers $query): string
    {
        $params = [
            'sort' => $query->sort,
            'page' => $query->page,
            'limit' => $query->limit,
        ];
        if(isset($query->filter['trainingTypeId'])) {
            $params['trainingTypeId'] = $query->filter['trainingTypeId'];
        }

        return 'trainers_' . md5(serialize($params));
    }
}
