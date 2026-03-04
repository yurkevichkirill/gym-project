<?php

declare(strict_types=1);

namespace App\Membership\Query;

use App\Client\Repository\ClientRepository;
use App\Membership\DTO\GetMemberships;
use App\Membership\Repository\MembershipRepository;
use Doctrine\ORM\QueryBuilder;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\CacheItem;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class MembershipQuery
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

        return $this->gymCache->get($cacheKey, function (CacheItem $item) use ($dto): array
        {
            $qb = $this->createQuery($dto->filter);

            $offset = ($dto->page - 1) * $dto->limit;

            foreach ($dto->sort as $alias => $order) {
                $field = self::SORT_MAP[$alias] ?? "m.$alias";
                $qb->addOrderBy("$field", $order);
            }
            $qb->setFirstResult($offset)
                ->setMaxResults($dto->limit);

            $item->tag(['memberships_list']);

            return $qb->getQuery()->getResult();
        });
    }

    public function getTotal(array $filter): int
    {
        return $this->createQuery($filter, true)->select("COUNT(m.id)")->getQuery()->getSingleScalarResult();
    }

    private function createQuery(array $filter, bool $isCount = false): QueryBuilder
    {
        $qb = $this->membershipRepo->createQueryBuilder('m');

        if(isset($filter['client'])) {
            $qb->andWhere('m.client = :client')
                ->setParameter('client', $filter['client']);
        }

        if(isset($filter['status'])) {
            $qb->andWhere('m.status = :status')
                ->setParameter('status', $filter['status']);
        }

        if(isset($filter['membershipPlan'])) {
            $qb->andWhere('m.plan = :membershipPlan')
                ->setParameter('membershipPlan', $filter['membershipPlan']);
        }

        if(isset($filter['minVisits'])) {
            $qb->andWhere('m.visits >= :minVisits')
                ->setParameter('minVisits', $filter['minVisits']);
        }

        if(isset($filter['maxVisits'])) {
            $qb->andWhere('m.visits <= :maxVisits')
                ->setParameter('maxVisits', $filter['maxVisits']);
        }

        return $qb;
    }

    private function generateCacheKey(GetMemberships $query): string
    {
        $params = [
            'client' => $query->filter['client'],
            'sort' => $query->sort,
            'page' => $query->page,
            'limit' => $query->limit,
        ];

        if(isset($query->filter['status'])) {
            $params['status'] = $query->filter['status'];
        }
        if(isset($query->filter['membershipPlan'])) {
            $params['membershipPlan'] = $query->filter['membershipPlan'];
        }
        if(isset($query->filter['minVisits'])) {
            $params['minVisits'] = $query->filter['minVisits'];
        }
        if(isset($query->filter['maxVisits'])) {
            $params['maxVisits'] = $query->filter['maxVisits'];
        }

        return 'memberships_' . md5(serialize($params));
    }
}
