<?php

declare(strict_types=1);

namespace App\Payment\Query;

use App\Payment\DTO\GetPayments;
use App\Payment\DTO\PaymentFilter;
use App\Payment\Repository\PaymentRepository;
use Doctrine\ORM\QueryBuilder;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\ItemInterface;
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

        return $this->gymCache->get($cacheKey, function (ItemInterface $item, bool $save) use ($dto): array {
            $item->expiresAfter(3600);

            $qb = $this->createQuery($dto->filter);

            $offset = ($dto->page - 1) * $dto->limit;

            foreach ($dto->sort as $field => $order) {
                $qb->addOrderBy("p.$field", $order);
            }

            $qb->setFirstResult($offset)
                ->setMaxResults($dto->limit);

            if ($dto->filter->client) {
                $item->tag(['payments_list_' . $dto->filter->client->getId()]);
            } else {
                $item->tag(['payments_list_all']);
            }

            return $qb->getQuery()->getResult();
        });
    }

    public function getTotal(PaymentFilter $filter): int
    {
        return (int) $this->createQuery($filter)
            ->select("COUNT(p.id)")
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function createQuery(PaymentFilter $filter): QueryBuilder
    {
        $qb = $this->paymentRepo->createQueryBuilder('p')
            ->leftJoin("p.trainer", "t")
            ->addSelect("t")
            ->leftJoin("t.trainingType", 'type')
            ->addSelect("type");

        if ($filter->client) {
            $qb->andWhere('p.client = :client')
                ->setParameter('client', $filter->client);
        }

        if ($filter->trainer) {
            $qb->andWhere('p.trainer = :trainer')
                ->setParameter('trainer', $filter->trainer);
        }

        if ($filter->minAmount !== null) {
            $qb->andWhere('p.amount >= :minAmount')
                ->setParameter('minAmount', $filter->minAmount);
        }

        if ($filter->maxAmount !== null) {
            $qb->andWhere('p.amount <= :maxAmount')
                ->setParameter('maxAmount', $filter->maxAmount);
        }

        if ($filter->isRefund !== null) {
            $qb->andWhere('p.isRefund = :isRefund')
                ->setParameter('isRefund', $filter->isRefund);
        }

        if ($filter->status) {
            $qb->andWhere('p.status = :status')
                ->setParameter('status', $filter->status);
        }

        if ($filter->minCreatedAt) {
            $qb->andWhere('p.createdAt >= :minCreatedAt')
                ->setParameter('minCreatedAt', $filter->minCreatedAt);
        }

        if ($filter->maxCreatedAt) {
            $qb->andWhere('p.createdAt <= :maxCreatedAt')
                ->setParameter('maxCreatedAt', $filter->maxCreatedAt);
        }

        return $qb;
    }

    private function generateCacheKey(GetPayments $query): string
    {
        $f = $query->filter;

        $params = [
            'sort' => $query->sort,
            'page' => $query->page,
            'limit' => $query->limit,
            'clientId' => $f->client?->getId(),
            'trainerId' => $f->trainer?->getId(),
            'minAmount' => $f->minAmount,
            'maxAmount' => $f->maxAmount,
            'isRefund' => $f->isRefund,
            'status' => $f->status?->value,
            'minCreatedAt' => $f->minCreatedAt?->format('Y-m-d'),
            'maxCreatedAt' => $f->maxCreatedAt?->format('Y-m-d'),
        ];

        return 'payments_' . md5(json_encode($params));
    }
}
