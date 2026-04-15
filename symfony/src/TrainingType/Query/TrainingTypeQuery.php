<?php

declare(strict_types=1);

namespace App\TrainingType\Query;

use App\TrainingType\DTO\GetTrainingTypes;
use App\TrainingType\Repository\TrainingTypeRepository;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\CacheItem;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final readonly class TrainingTypeQuery
{
    public function __construct(
        private TrainingTypeRepository $repo,
        private TagAwareCacheInterface $cache
    ) {}

    /**
     * @throws InvalidArgumentException
     */
    public function handle(GetTrainingTypes $dto): array
    {
        $cacheKey = $this->generateCacheKey($dto);

        return $this->cache->get($cacheKey, function (CacheItem $item) use ($dto) {
            $item->expiresAfter(3600);

            $qb = $this->repo->createQueryBuilder('t');

            foreach ($dto->sort as $field => $order) {
                $qb->addOrderBy("t.$field", $order);
            }

            $qb->setFirstResult(($dto->page - 1) * $dto->limit)
                ->setMaxResults($dto->limit);

            $item->tag(['training_types_list']);

            return $qb->getQuery()->getResult();
        });
    }

    public function getTotal(): int
    {
        return (int) $this->repo->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function generateCacheKey(GetTrainingTypes $dto): string
    {
        return 'training_types_' . md5(serialize([
                'sort' => $dto->sort,
                'page' => $dto->page,
                'limit' => $dto->limit,
            ]));
    }
}
