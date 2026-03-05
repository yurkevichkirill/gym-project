<?php

declare(strict_types=1);

namespace App\Payment\Query;

use App\Client\Repository\ClientRepository;
use App\Payment\DTO\GetPayments;
use App\Payment\Repository\PaymentRepository;
use App\Trainer\Repository\TrainerRepository;
use Doctrine\ORM\QueryBuilder;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\CacheItem;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final readonly class PaymentsQuery
{
    public function __construct(
        private PaymentRepository $paymentRepo,
        private TrainerRepository $trainerRepo,
        private ClientRepository $clientRepo,
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

            $qb = $this->paymentRepo->createQueryBuilder('p');

            if(isset($dto->filter['clientId'])) {
                $qb->andWhere('p.client = :client')
                    ->setParameter('client', $this->clientRepo->find($dto->filter['clientId']));
            }

            if(isset($dto->filter['trainerId'])) {
                $qb->andWhere('p.trainerId = :trainerId')
                    ->setParameter('trainerId', $this->trainerRepo->find($dto->filter['trainerId']));
            }

            if(isset($dto->filter['minAmount'])) {
                $qb->andWhere('p.amount >= :minAmount')
                    ->setParameter('minAmount', $dto->filter['minAmount']);
            }

            if(isset($dto->filter['maxAmount'])) {
                $qb->andWhere('p.amount <= :maxAmount')
                    ->setParameter('maxAmount', $dto->filter['maxAmount']);
            }

            if(isset($dto->filter['category'])) {
                $qb->andWhere('p.category = :category')
                    ->setParameter('category', $dto->filter['category']);
            }

            $offset = ($dto->page - 1) * $dto->limit;

            foreach ($dto->sort as $field => $order) {
                $qb->addOrderBy("p.$field", $order);
            }
            $qb->setFirstResult($offset)
                ->setMaxResults($dto->limit);

            $item->tag(['payments_list_' . $dto->filter['client']->getId()]);

            return $qb->getQuery()->getResult();
        });
    }

    public function getTotal(array $filter): int
    {
        return $this->createQuery($filter)->select("COUNT(p.id)")->getQuery()->getSingleScalarResult();
    }

    private function createQuery(array $filter): QueryBuilder
    {
        $qb = $this->paymentRepo->createQueryBuilder('p');

        if(isset($filter['client'])) {
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

        if(isset($query->filter['client'])) {
            $params['client'] = $query->filter['client'];
        } else {
            $params['client'] = null;
        }
        if(isset($query->filter['trainer'])) {
            $params['trainer'] = $query->filter['trainer'];
        }
        if(isset($query->filter['minAmount'])) {
            $params['minAmount'] = $query->filter['minAmount'];
        }
        if(isset($query->filter['maxAmount'])) {
            $params['maxAmount'] = $query->filter['maxAmount'];
        }
        if(isset($query->filter['category'])) {
            $params['category'] = $query->filter['category'];
        }

        return 'payments_' . md5(serialize($params));
    }
}
