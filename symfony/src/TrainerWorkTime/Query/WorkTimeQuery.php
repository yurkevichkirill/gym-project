<?php

declare(strict_types=1);

namespace App\TrainerWorkTime\Query;

use App\Request\SortParser;
use App\TrainerWorkTime\DTO\ResolvedWorktimesRequestDTO;
use App\TrainerWorkTime\Mapper\WorkTimeMapperInterface;
use App\TrainerWorkTime\Repository\TrainerWorkTimeRepository;
use Doctrine\ORM\QueryBuilder;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final readonly class WorkTimeQuery
{
    public function __construct(
        private TrainerWorkTimeRepository $worktimeRepo,
        private WorkTimeMapperInterface $mapper,
        private TagAwareCacheInterface $cache,
    )
    {}

    /**
     * @param array<string, string> $parsedSort
     * @return array{items: list<mixed>, total: int}
     * @throws InvalidArgumentException
     */
    public function getCachedData(ResolvedWorktimesRequestDTO $dto, array $parsedSort): array
    {
        $cacheKey = $this->generateCacheKey($dto);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($dto, $parsedSort): array {

            $item->expiresAfter(3600);

            if ($dto->trainer !== null) {
                $item->tag(['trainer_worktimes_list_' . $dto->trainer->getId()]);
            } else {
                $item->tag(['trainer_worktimes_list_all']);
            }

            $qb = $this->createQuery($dto);

            $totalQb = $this->createQuery($dto, true);
            $total = (int) $totalQb->select('COUNT(w.id)')->getQuery()->getSingleScalarResult();

            $offset = ($dto->page - 1) * $dto->limit;

            foreach ($parsedSort as $field => $order) {
                $qb->addOrderBy("w.$field", $order);
            }

            $qb->setFirstResult($offset)
                ->setMaxResults($dto->limit);

            $worktimes = $qb->getQuery()->getResult();

            $items = array_map(fn ($worktime) => $this->mapper->map($worktime), $worktimes);

            return [
                'items' => $items,
                'total' => $total,
            ];
        });
    }

    private function createQuery(ResolvedWorktimesRequestDTO $dto, bool $isCount = false): QueryBuilder
    {
        $qb = $this->worktimeRepo->createQueryBuilder('w')
            ->leftJoin('w.trainer', 'trainer');

        $qb->andWhere('trainer.deletedAt IS NULL')
            ->andWhere('trainer.blockedAt IS NULL');

        if (!$isCount) {
            $qb->addSelect('trainer')
                ->leftJoin('w.trainings', 'tr')
                ->addSelect('tr')
                ->leftJoin('trainer.trainingType', 'type')
                ->addSelect('type');
        }

        if ($dto->trainer !== null) {
            $qb->andWhere('trainer = :trainer')
                ->setParameter('trainer', $dto->trainer);
        }

        if ($dto->date !== null) {
            $qb->andWhere('w.date = :date')
                ->setParameter('date', $dto->date);
        }

        return $qb;
    }

    /**
     * @return array<string, string>
     * @throws BadRequestHttpException
     */
    public function getParsedSort(ResolvedWorktimesRequestDTO $dto): array
    {
        return SortParser::parseSort($dto->sort, ResolvedWorktimesRequestDTO::ALLOWED_SORT_FIELDS);
    }

    private function generateCacheKey(ResolvedWorktimesRequestDTO $dto): string
    {
        $params = [
            'sort' => $dto->sort,
            'page' => $dto->page,
            'limit' => $dto->limit,
            'trainerId' => $dto->trainer?->getId(),
            'date' => $dto->date?->format('Y-m-d'),
        ];

        $encoded = json_encode($params);

        return 'trainer_worktime_' . hash('sha256', $encoded === false ? '' : $encoded);
    }
}
