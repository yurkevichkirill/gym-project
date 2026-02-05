<?php

declare(strict_types=1);

namespace App\Payment\Service;

use App\Client\Entity\Client;
use App\Client\Repository\ClientRepository;
use App\Payment\Entity\Payment;
use App\Payment\Enum\PaymentCategoryEnum;
use App\Payment\Enum\PaymentStatusEnum;
use App\Payment\Repository\PaymentRepository;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Cache\CacheItem;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class PaymentService implements PaymentServiceInterface
{
    public function __construct(
        private ClientRepository $clientRepo,
        private PaymentRepository $paymentRepo,
        private TagAwareCacheInterface $cacheGym
    )
    {}

    public function pay(Client $client, Payment $payment): void
    {
        if($payment->getStatus() === PaymentStatusEnum::PAID) {
            return;
        }
        $newBalance = $client->getBalance() - $payment->getAmount();
        $client->setBalance((string) $newBalance);
        $payment->setStatus(PaymentStatusEnum::PAID);
    }

    public function cancel(Payment $payment): void
    {
        $payment->setStatus(PaymentStatusEnum::CANCELLED);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function findBy(array $sort, ?int $clientId = null, ?PaymentCategoryEnum $category = null, ?PaymentStatusEnum $status = null): array
    {
        $cacheKey = $this->generateCacheKey($sort, $clientId, $category, $status);

        return $this->cacheGym->get($cacheKey, function (CacheItem $item) use ($sort, $clientId, $category, $status): array
        {
            $item->tag(['payments_list']);

            $criteria = [];
            if($clientId) {
                $client = $this->clientRepo->find($clientId);
                $criteria['client'] = $client;
            }
            if($category) {
                $criteria['category'] = $category;
            }
            if($status) {
                $criteria['status'] = $status;
            }

            return $this->paymentRepo->findBy($criteria, $sort);
        });
    }

    public function generateCacheKey(array $sort, ?int $clientId, ?PaymentCategoryEnum $category, ?PaymentStatusEnum $status): string
    {
        $params = [
            'sort' => $sort,
            'category' => $category,
            'status' => $status
        ];

        return 'payments_' . md5(serialize($params));
    }
}
//    public function enrollOnTraining(Trainer $trainer, Client $client, DayOfWeekEnum $dayOfWeek, \DateTimeImmutable $startTrainingTime, int $duration): void
//    {
//        $endTrainingTime = $startTrainingTime->add(new \DateInterval('PT' . $duration . 'M')) ;
//        $available = $this->getAvailable($trainer, $dayOfWeek);
//        if($this->isTimeAvailable($available, $startTrainingTime, $endTrainingTime)) {
//            $payment = new Payment();
//            $payment->setClient($client);
//            $payment->setAmount($trainer->getPrice());
//            $payment->setCategory(PaymentCategoryEnum::TRAINER);
//
//        }
//    }
//
//    public function isTimeAvailable(array $available, \DateTimeImmutable $startTrainingTime, \DateTimeImmutable $endTrainingTime): bool
//    {
//        $startTimes = array_keys($available);
//        foreach ($available as $startPeriod => $endPeriod) {
//            if($startTrainingTime >= $startPeriod && $endTrainingTime <= $endPeriod) {
//
//                return true;
//            }
//        }
//
//        return false;
//    }
//}
//
////            $payment->setCategory(PaymentCategoryEnum::TRAINER);
////
////        }
////    }
////
////    public function isTimeAvailable(array $available, \DateTimeImmutable $startTrainingTime, \DateTimeImmutable $endTrainingTime): bool
////    {
////        $startTimes = array_keys($available);
////        foreach ($available as $startPeriod => $endPeriod) {
////            if($startTrainingTime >= $startPeriod && $endTrainingTime <= $endPeriod) {
////
////                return true;
////            }
////        }
////
////        return false;
////    }
////}
