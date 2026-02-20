<?php

declare(strict_types=1);

namespace App\TrainingType\Query;

use App\Trainer\Repository\TrainerRepository;
use App\Training\DTO\GetTrainings;
use App\Training\Repository\TrainingRepository;
use App\TrainingType\DTO\GetTrainingTypes;
use App\TrainingType\Repository\TrainingTypeRepository;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\CacheItem;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class TrainingTypeQuery
{
    public function __construct(
        private TrainingTypeRepository     $trainingTypeRepo,
        private TagAwareCacheInterface $gymCache
    )
    {}

    /**
     * @throws InvalidArgumentException
     */
    public function handle(GetTrainingTypes $dto): array
    {
        $cacheKey = $this->generateCacheKey($dto);

        return $this->gymCache->get($cacheKey, function (CacheItem $item) use ($dto): array
        {
            $qb = $this->trainingTypeRepo->createQueryBuilder('t');

            $offset = ($dto->page - 1) * $dto->limit;

            foreach ($dto->sort as $field => $order) {
                $qb->addOrderBy("t.$field", $order);
            }
            $qb->setFirstResult($offset)
                ->setMaxResults($dto->limit);

            $item->tag(['training_types_list']);

            return $qb->getQuery()->getResult();
        });
    }

    private function generateCacheKey(GetTrainingTypes $query): string
    {
        $params = [
            'sort' => $query->sort,
            'page' => $query->page,
            'limit' => $query->limit,
        ];

        return 'training_types_' . md5(serialize($params));
    }
}
