<?php

declare(strict_types=1);

namespace App\Membership\Query;

use App\Client\Repository\ClientRepository;
use App\Membership\DTO\GetMemberships;
use App\Membership\Repository\MembershipRepository;
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
        private ClientRepository $clientRepo,
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
            $qb = $this->membershipRepo->createQueryBuilder('m')
                ->addSelect('p')
                ->innerJoin('m.plan', 'p');

            if(isset($dto->filter['clientId'])) {
                $qb->andWhere('m.client = :client')
                    ->setParameter('client', $this->clientRepo->find($dto->filter['clientId']));
            }

            if(isset($dto->filter['status'])) {
                $qb->andWhere('m.status = :status')
                    ->setParameter('status', $dto->filter['status']);
            }

            if(isset($dto->filter['membershipPlanId'])) {
                $qb->andWhere('p.id = :membershipPlanId')
                    ->setParameter('membershipPlanId', $dto->filter['membershipPlanId']);
            }

            if(isset($dto->filter['minVisits'])) {
                $qb->andWhere('m.visits >= :minVisits')
                    ->setParameter('minVisits', $dto->filter['minVisits']);
            }

            if(isset($dto->filter['maxVisits'])) {
                $qb->andWhere('m.visits <= :maxVisits')
                    ->setParameter('maxVisits', $dto->filter['maxVisits']);
            }

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

    private function generateCacheKey(GetMemberships $query): string
    {
        $params = [
            'clientId' => $query->filter['clientId'],
            'sort' => $query->sort,
            'page' => $query->page,
            'limit' => $query->limit,
        ];

        if(isset($query->filter['status'])) {
            $params['status'] = $query->filter['status'];
        }
        if(isset($query->filter['membershipPlanId'])) {
            $params['membershipPlanId'] = $query->filter['membershipPlanId'];
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
