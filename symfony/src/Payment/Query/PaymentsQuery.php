<?php

declare(strict_types=1);

namespace App\Payment\Query;

use App\Payment\DTO\GetPayments;
use App\Payment\Repository\PaymentRepository;
use Doctrine\ORM\QueryBuilder;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\CacheItem;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final readonly class PaymentsQuery
{
    public function __construct(
        private PaymentRepository $paymentRepo,
        private TagAwareCacheInterface $gymCache
    )
    {}

    /**
     * @throws InvalidArgumentException
     */
    public function handle(GetPayments $dto): array
    {
        $cacheKey = $this->generateCacheKey($dto);

        return $this->gymCache->get($cacheKey, function (CacheItem $item) use ($dto): array
        {
            $item->expiresAfter(3600);

            $qb = $this->createQuery($dto->filter);

            $offset = ($dto->page - 1) * $dto->limit;

            foreach ($dto->sort as $field => $order) {
                $qb->addOrderBy("p.$field", $order);
            }
            $qb->setFirstResult($offset)
                ->setMaxResults($dto->limit);

            if (isset($dto->filter['client'])) {
                $item->tag(['payments_list_' . $dto->filter['client']->getId()]);
            } else {
                $item->tag(["payments_list_all"]);
            }
            return $qb->getQuery()->getResult();
        });
    }

    public function getTotal(array $filter): int
    {
        return $this->createQuery($filter)->select("COUNT(p.id)")->getQuery()->getSingleScalarResult();
    }

    private function createQuery(array $filter): QueryBuilder
    {
        $qb = $this->paymentRepo->createQueryBuilder('p')
            ->leftJoin("p.trainer", "t")
            ->addSelect("t")
            ->leftJoin("t.trainingType", 'type')
            ->addSelect("type");

        if (isset($filter['client'])) {
            $qb->andWhere('p.client = :client')
                ->setParameter('client', $filter['client']);
        }

        if(isset($filter['trainer'])) {
            $qb->andWhere('p.trainer = :trainer')
                ->setParameter('trainer', $filter['trainer']);
        }

        if(isset($filter['minAmount'])) {
            $qb->andWhere('p.amount >= :minAmount')
                ->setParameter('minAmount', $filter['minAmount']);
        }

        if(isset($filter['maxAmount'])) {
            $qb->andWhere('p.amount <= :maxAmount')
                ->setParameter('maxAmount', $filter['maxAmount']);
        }

        if(isset($filter['category'])) {
            $qb->andWhere('p.category = :category')
                ->setParameter('category', $filter['category']);
        }

        return $qb;
    }

    private function generateCacheKey(GetPayments $query): string
    {
        $params = [
            'sort' => $query->sort,
            'page' => $query->page,
            'limit' => $query->limit,
        ];

        if (isset($query->filter['client'])) {
            $params['client'] = $query->filter['client'];
        }
        if (isset($query->filter['trainer'])) {
            $params['trainer'] = $query->filter['trainer'];
        }
        if (isset($query->filter['minAmount'])) {
            $params['minAmount'] = $query->filter['minAmount'];
        }
        if (isset($query->filter['maxAmount'])) {
            $params['maxAmount'] = $query->filter['maxAmount'];
        }
        if (isset($query->filter['category'])) {
            $params['category'] = $query->filter['category'];
        }

        return 'payments_' . md5(serialize($params));
    }
}
