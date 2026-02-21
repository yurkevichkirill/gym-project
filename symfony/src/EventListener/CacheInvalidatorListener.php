<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Booking\Entity\Booking;
use App\Client\Entity\Client;
use App\Membership\Entity\Membership;
use App\MembershipPlan\Entity\MembershipPlan;
use App\Payment\Entity\Payment;
use App\Trainer\Entity\Trainer;
use App\TrainerWorkTime\Entity\TrainerWorkTime;
use App\Training\Entity\Training;
use App\TrainingType\Entity\TrainingType;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

readonly class CacheInvalidatorListener
{
    public function __construct(
        private TagAwareCacheInterface $gymCache
    )
    {}

    public function onFlush(OnFlushEventArgs $eventArgs): void
    {
        $em = $eventArgs->getObjectManager();
        $uow = $em->getUnitOfWork();

        foreach (array_merge(
            $uow->getScheduledEntityInsertions(),
            $uow->getScheduledEntityUpdates(),
            $uow->getScheduledEntityDeletions()
        ) as $entity) {
            match(true) {
                $entity instanceof Client => $this->gymCache->invalidateTags(['clients_list']),
                $entity instanceof Booking => $this->gymCache->invalidateTags(['bookings_list']),
                $entity instanceof MembershipPlan => $this->gymCache->invalidateTags(['membership_plans_list']),
                $entity instanceof Membership => $this->gymCache->invalidateTags(['memberships_list']),
                $entity instanceof Payment => $this->gymCache->invalidateTags(['payments_list']),
                $entity instanceof Trainer => $this->gymCache->invalidateTags(['trainers_list']),
                $entity instanceof TrainerWorkTime => $this->gymCache->invalidateTags(['trainer_worktimes_list']),
                $entity instanceof Training => $this->gymCache->invalidateTags(['trainings_list', 'trainer_worktimes_list']),
                $entity instanceof TrainingType => $this->gymCache->invalidateTags(['training_types_list']),

                default => null
            };
        }
    }
}
