<?php

declare(strict_types=1);

namespace App\Payment\Service;

use App\Client\Entity\Client;
use App\Payment\Entity\Payment;
use App\Payment\Enum\PaymentStatusEnum;

class PaymentService implements PaymentServiceInterface
{
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
