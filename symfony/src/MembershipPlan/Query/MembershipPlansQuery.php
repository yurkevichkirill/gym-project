<?php

declare(strict_types=1);

namespace App\MembershipPlan\Query;

use App\MembershipPlan\DTO\GetMembershipPlansRequestDTO;
use App\MembershipPlan\Mapper\MembershipPlanMapperInterface;
use App\MembershipPlan\Repository\MembershipPlanRepository;
use App\Request\SortParser;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final readonly class MembershipPlansQuery
{
    public function __construct(
        private MembershipPlanRepository $repo,
        private TagAwareCacheInterface $cache,
        private MembershipPlanMapperInterface $mapper,
    ) {}

    /**
     * @throws InvalidArgumentException
     */
    public function getCachedData(GetMembershipPlansRequestDTO $dto, array $parsedSort): array
    {
        $cacheKey = $this->generateCacheKey($dto);

        return $this->cache->get($cacheKey, function (ItemInterface $item, bool $save) use ($dto, $parsedSort) {
            $item->expiresAfter(3600);
            $item->tag(['membership_plans_list']);

            $qb = $this->createQuery($dto);

            $totalQb = clone $qb;
            $total = (int) $totalQb->select('COUNT(m.id)')->getQuery()->getSingleScalarResult();

            foreach ($parsedSort as $field => $order) {
                $qb->addOrderBy("m.$field", $order);
            }

            $qb->setFirstResult(($dto->page - 1) * $dto->limit)
                ->setMaxResults($dto->limit);

            $plans = $qb->getQuery()->getResult();

            $items = array_map(fn ($plan) => $this->mapper->map($plan), $plans);

            return [
                'items' => $items,
                'total' => $total,
            ];
        });
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getTotal(GetMembershipPlansRequestDTO $dto): int
    {
        return (int) $this->createQuery($dto)
            ->select('COUNT(m.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function createQuery(GetMembershipPlansRequestDTO $dto): QueryBuilder
    {
        $qb = $this->repo->createQueryBuilder('m');

        if ($dto->minPrice !== null) {
            $qb->andWhere('m.price >= :minPrice')
                ->setParameter('minPrice', $dto->minPrice);
        }

        if ($dto->maxPrice !== null) {
            $qb->andWhere('m.price <= :maxPrice')
                ->setParameter('maxPrice', $dto->maxPrice);
        }

        if ($dto->minDurationDays !== null) {
            $qb->andWhere('m.durationDays >= :minDurationDays')
                ->setParameter('minDurationDays', $dto->minDurationDays);
        }

        if ($dto->maxDurationDays !== null) {
            $qb->andWhere('m.durationDays <= :maxDurationDays')
                ->setParameter('maxDurationDays', $dto->maxDurationDays);
        }

        if ($dto->minSessionLimit !== null) {
            $qb->andWhere('m.sessionLimit >= :minSessionLimit')
                ->setParameter('minSessionLimit', $dto->minSessionLimit);
        }

        if ($dto->maxSessionLimit !== null) {
            $qb->andWhere('m.sessionLimit <= :maxSessionLimit')
                ->setParameter('maxSessionLimit', $dto->maxSessionLimit);
        }

        if ($dto->isUnlimited === true) {
            $qb->andWhere('m.sessionLimit IS NULL');
        }

        if ($dto->isUnlimited === false) {
            $qb->andWhere('m.sessionLimit IS NOT NULL');
        }

        return $qb;
    }

    /**
     * @throws BadRequestHttpException
     */
    public function getParsedSort(GetMembershipPlansRequestDTO $dto): array
    {
        return SortParser::parseSort($dto->sort, GetMembershipPlansRequestDTO::ALLOWED_SORT_FIELDS);
    }

    private function generateCacheKey(GetMembershipPlansRequestDTO $dto): string
    {
        return 'membership_plans_' . md5(serialize([
                'sort' => $dto->sort,
                'page' => $dto->page,
                'limit' => $dto->limit,
                'minPrice' => $dto->minPrice,
                'maxPrice' => $dto->maxPrice,
                'minDurationDays' => $dto->minDurationDays,
                'maxDurationDays' => $dto->maxDurationDays,
                'minSessionLimit' => $dto->minSessionLimit,
                'maxSessionLimit' => $dto->maxSessionLimit,
                'isUnlimited' => $dto->isUnlimited,
            ]));
    }
}
