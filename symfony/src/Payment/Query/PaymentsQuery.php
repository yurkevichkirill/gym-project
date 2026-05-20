<?php

declare(strict_types=1);

namespace App\Payment\Query;

use App\Payment\DTO\ResolvedPaymentsRequestDTO;
use App\Payment\Mapper\PaymentMapperInterface;
use App\Payment\Repository\PaymentRepository;
use App\Request\SortParser;
use Doctrine\ORM\QueryBuilder;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final readonly class PaymentsQuery
{
    public function __construct(
        private PaymentMapperInterface $mapper,
        private PaymentRepository $paymentRepo,
        private TagAwareCacheInterface $cache,
    )
    {}

    /**
     * @param array<string, string> $parsedSort
     * @return array{items: list<mixed>, total: int}
     * @throws InvalidArgumentException
     */
    public function getCachedData(ResolvedPaymentsRequestDTO $dto, array $parsedSort): array
    {
        $cacheKey = $this->generateCacheKey($dto);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($dto, $parsedSort): array {
            $item->expiresAfter(3600);

            $tags = [];

            if ($dto->client !== null) {
                $tags[] = 'payments_list_' . $dto->client->getId();
            }
            if ($dto->trainer !== null) {
                $tags[] = 'payments_list_trainer_' . $dto->trainer->getId();
            }
            if ($tags === []) {
                $tags[] = 'payments_list_all';
            }

            $item->tag($tags);

            $qb = $this->createQuery($dto);

            $totalQb = $this->createQuery($dto, true);
            $total = (int) $totalQb->select('COUNT(p.id)')->getQuery()->getSingleScalarResult();

            $offset = ($dto->page - 1) * $dto->limit;

            foreach ($parsedSort as $field => $order) {
                $qb->addOrderBy("p.$field", $order);
            }

            $qb->setFirstResult($offset)
                ->setMaxResults($dto->limit);

            $payments = $qb->getQuery()->getResult();

            $items = array_map(fn ($payment) => $this->mapper->map($payment), $payments);

            return [
                'items' => $items,
                'total' => $total,
            ];
        });
    }

    private function createQuery(ResolvedPaymentsRequestDTO $dto, bool $isCount = false): QueryBuilder
    {
        $qb = $this->paymentRepo->createQueryBuilder('p');

        if (!$isCount) {
            $qb->leftJoin('p.trainer', 't')
                ->addSelect('t')
                ->leftJoin('t.trainingType', 'type')
                ->addSelect('type');
        }

        if ($dto->client !== null) {
            $qb->andWhere('p.client = :client')
                ->setParameter('client', $dto->client);
        }

        if ($dto->trainer !== null) {
            $qb->andWhere('p.trainer = :trainer')
                ->setParameter('trainer', $dto->trainer);
        }

        if ($dto->minAmount !== null) {
            $qb->andWhere('p.amount >= :minAmount')
                ->setParameter('minAmount', $dto->minAmount);
        }

        if ($dto->maxAmount !== null) {
            $qb->andWhere('p.amount <= :maxAmount')
                ->setParameter('maxAmount', $dto->maxAmount);
        }

        if ($dto->isRefund !== null) {
            $qb->andWhere('p.isRefund = :isRefund')
                ->setParameter('isRefund', $dto->isRefund);
        }

        if ($dto->status !== null) {
            $qb->andWhere('p.status = :status')
                ->setParameter('status', $dto->status);
        }

        if ($dto->minCreatedAt !== null) {
            $minDate = $dto->minCreatedAt->setTime(0, 0, 0);
            $qb->andWhere('p.createdAt >= :minCreatedAt')
                ->setParameter('minCreatedAt', $minDate);
        }

        if ($dto->maxCreatedAt !== null) {
            $maxDate = $dto->maxCreatedAt->setTime(23, 59, 59);
            $qb->andWhere('p.createdAt <= :maxCreatedAt')
                ->setParameter('maxCreatedAt', $maxDate);
        }

        return $qb;
    }

    /**
     * @return array<string, string>
     * @throws BadRequestHttpException
     */
    public function getParsedSort(ResolvedPaymentsRequestDTO $dto): array
    {
        return SortParser::parseSort($dto->sort, ResolvedPaymentsRequestDTO::ALLOWED_SORT_FIELDS);
    }

    private function generateCacheKey(ResolvedPaymentsRequestDTO $dto): string
    {
        $params = [
            'sort' => $dto->sort,
            'page' => $dto->page,
            'limit' => $dto->limit,
            'clientId' => $dto->client?->getId(),
            'trainerId' => $dto->trainer?->getId(),
            'minAmount' => $dto->minAmount,
            'maxAmount' => $dto->maxAmount,
            'isRefund' => $dto->isRefund,
            'status' => $dto->status?->value,
            'minCreatedAt' => $dto->minCreatedAt?->format('Y-m-d'),
            'maxCreatedAt' => $dto->maxCreatedAt?->format('Y-m-d'),
        ];

        $encoded = json_encode($params);

        return 'payments_' . hash('sha256', $encoded === false ? '' : $encoded);
    }
}
