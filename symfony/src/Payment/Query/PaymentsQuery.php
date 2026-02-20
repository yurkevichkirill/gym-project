<?php

declare(strict_types=1);

namespace App\Payment\Query;

use App\Client\Repository\ClientRepository;
use App\Payment\DTO\GetPayments;
use App\Payment\DTO\PaymentResponse;
use App\Payment\Repository\PaymentRepository;
use App\Trainer\Repository\TrainerRepository;
use App\Training\DTO\GetTrainings;
use App\Training\Repository\TrainingRepository;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\CacheItem;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class PaymentsQuery
{
    private const array SORT_MAP = [
        'clientId' => 'c.id',
        'date' => 'w.date',
        'status' => 'b.status',
        'bookedAt' => 'b.bookedAt',
    ];

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

            $qb = $this->paymentRepo->createQueryBuilder('p');

            if(isset($dto->filter['clientId'])) {
                $qb->andWhere('p.id = :clientId')
                    ->setParameter('clientId', $this->clientRepo->find($dto->filter['clientId']));
            }

            if(isset($dto->filter['trainerId'])) {
                $qb->andWhere('p.trainerId = :trainerId')
                    ->setParameter('trainerId', $this->trainerRepo->find($dto->filter['trainerId']));
            }

            if(isset($dto->filter['amount'])) {
                $qb->andWhere('p.amount = :amount')
                    ->setParameter('amount', $dto->filter['amount']);
            }

            if(isset($dto->filter['category'])) {
                $qb->andWhere('p.category = :category')
                    ->setParameter('category', $dto->filter['category']);
            }

            if(isset($dto->filter['status'])) {
                $qb->andWhere('p.status = :status')
                    ->setParameter('status', $dto->filter['status']);
            }

            $offset = ($dto->page - 1) * $dto->limit;

            foreach ($dto->sort as $field => $order) {
                $qb->addOrderBy("p.$field", $order);
            }
            $qb->setFirstResult($offset)
                ->setMaxResults($dto->limit);

            $item->tag(['payments_list']);

            return $qb->getQuery()->getResult();
        });
    }

    private function generateCacheKey(GetPayments $query): string
    {
        $params = [
            'sort' => $query->sort,
            'page' => $query->page,
            'limit' => $query->limit,
        ];

        if(isset($query->filter['clientId'])) {
            $params['clientId'] = $query->filter['clientId'];
        }
        if(isset($query->filter['trainerId'])) {
            $params['trainerId'] = $query->filter['trainerId'];
        }
        if(isset($query->filter['amount'])) {
            $params['amount'] = $query->filter['amount'];
        }
        if(isset($query->filter['category'])) {
            $params['category'] = $query->filter['category'];
        }
        if(isset($query->filter['status'])) {
            $params['status'] = $query->filter['status'];
        }

        return 'payments_' . md5(serialize($params));
    }
}
