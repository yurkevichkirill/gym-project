<?php

declare(strict_types=1);

namespace App\Membership\Query;

use App\Membership\DTO\GetMemberships;
use App\Membership\DTO\MembershipFilter;
use App\Membership\Repository\MembershipRepository;
use Doctrine\ORM\QueryBuilder;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final readonly class MembershipQuery
{
    private const array SORT_MAP = [
        'membershipPlanId' => 'p.id',
    ];

    public function __construct(
        private MembershipRepository $membershipRepo,
        private TagAwareCacheInterface $gymCache
    )
    {}

    /**
     * @throws InvalidArgumentException
     */
    public function handle(GetMemberships $dto): array
    {
        $cacheKey = $this->generateCacheKey($dto);

        return $this->gymCache->get($cacheKey, function (ItemInterface $item, bool $save) use ($dto): array {

            $item->expiresAfter(3600);

            $qb = $this->createQuery($dto->filter);

            $offset = ($dto->page - 1) * $dto->limit;

            foreach ($dto->sort as $alias => $order) {
                $field = self::SORT_MAP[$alias] ?? "m.$alias";
                $qb->addOrderBy($field, $order);
            }

            $qb->setFirstResult($offset)
                ->setMaxResults($dto->limit);

            if ($dto->filter->client) {
                $item->tag(["memberships_list_" . $dto->filter->client->getId()]);
            } else {
                $item->tag(["memberships_list_all"]);
            }

            return $qb->getQuery()->getResult();
        });
    }

    public function getTotal(MembershipFilter $filter): int
    {
        return (int) $this->createQuery($filter)
            ->select('COUNT(m.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function createQuery(MembershipFilter $filter): QueryBuilder
    {
        $qb = $this->membershipRepo->createQueryBuilder('m')
            ->leftJoin('m.plan', 'plan')
            ->addSelect('plan')
            ->leftJoin('m.payment', 'p')
            ->addSelect('p');

        if ($filter->client) {
            $qb->andWhere('m.client = :client')
                ->setParameter('client', $filter->client);
        }

        if ($filter->status !== null) {
            $qb->andWhere('m.status = :status')
                ->setParameter('status', $filter->status);
        }

        if ($filter->membershipPlan) {
            $qb->andWhere('m.plan = :plan')
                ->setParameter('plan', $filter->membershipPlan);
        }

        if ($filter->minVisits !== null) {
            $qb->andWhere('m.visits >= :minVisits')
                ->setParameter('minVisits', $filter->minVisits);
        }

        if ($filter->maxVisits !== null) {
            $qb->andWhere('m.visits <= :maxVisits')
                ->setParameter('maxVisits', $filter->maxVisits);
        }

        return $qb;
    }

    private function generateCacheKey(GetMemberships $query): string
    {
        $f = $query->filter;

        $params = [
            'sort' => $query->sort,
            'page' => $query->page,
            'limit' => $query->limit,
            'clientId' => $f->client?->getId(),
            'membershipPlanId' => $f->membershipPlan?->getId(),
            'status' => $f->status,
            'minVisits' => $f->minVisits,
            'maxVisits' => $f->maxVisits,
        ];

        return 'memberships_' . md5(json_encode($params));
    }
}
