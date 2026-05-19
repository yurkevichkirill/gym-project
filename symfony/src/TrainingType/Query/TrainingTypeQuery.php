<?php

declare(strict_types=1);

namespace App\TrainingType\Query;

use App\Request\SortParser;
use App\TrainingType\DTO\GetTrainingTypesRequestDTO;
use App\TrainingType\Mapper\TrainingTypeMapperInterface;
use App\TrainingType\Repository\TrainingTypeRepository;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final readonly class TrainingTypeQuery
{
    public function __construct(
        private TrainingTypeRepository $repo,
        private TrainingTypeMapperInterface $mapper,
        private TagAwareCacheInterface $cache
    ) {}

    /**
     * @param array<string, string> $parsedSort
     * @return array{items: list<mixed>, total: int}
     * @throws InvalidArgumentException
     */
    public function getCachedData(GetTrainingTypesRequestDTO $dto, array $parsedSort): array
    {
        $cacheKey = $this->generateCacheKey($dto);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($dto, $parsedSort): array {
            $item->expiresAfter(3600);

            $item->tag(['training_types_list']);

            $qb = $this->repo->createQueryBuilder('t');

            $totalQb = clone $qb;
            $total = (int) $totalQb->select('COUNT(t.id)')->getQuery()->getSingleScalarResult();

            foreach ($parsedSort as $field => $order) {
                $qb->addOrderBy("t.$field", $order);
            }

            $qb->setFirstResult(($dto->page - 1) * $dto->limit)
                ->setMaxResults($dto->limit);

            $trainingTypes = $qb->getQuery()->getResult();

            $items = array_map(fn ($trainingType) => $this->mapper->map($trainingType), $trainingTypes);

            return [
                'items' => $items,
                'total' => $total,
            ];
        });
    }

    /**
     * @return array<string, string>
     * @throws BadRequestHttpException
     */
    public function getParsedSort(GetTrainingTypesRequestDTO $dto): array
    {
        return SortParser::parseSort($dto->sort, GetTrainingTypesRequestDTO::ALLOWED_SORT_FIELDS);
    }

    private function generateCacheKey(GetTrainingTypesRequestDTO $dto): string
    {
        return 'training_types_' . hash('sha256', serialize([
            'sort' => $dto->sort,
            'page' => $dto->page,
            'limit' => $dto->limit,
        ]));
    }
}
