<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Booking\Entity\Booking;
use App\Cache\CacheVersionService;
use App\Client\Entity\Client;
use App\Membership\Entity\Membership;
use App\MembershipPlan\Entity\MembershipPlan;
use App\Payment\Entity\Payment;
use App\Trainer\Entity\Trainer;
use App\TrainerWorkTime\Entity\TrainerWorkTime;
use App\Training\Entity\Training;
use App\TrainingType\Entity\TrainingType;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

readonly class CacheInvalidatorListener
{
    public function __construct(
        private TagAwareCacheInterface $gymCache,
        private CacheVersionService $cacheVersionService,
    )
    {}

    /**
     * @throws InvalidArgumentException
     */
    public function onFlush(OnFlushEventArgs $eventArgs): void
    {
        $em = $eventArgs->getObjectManager();
        $uow = $em->getUnitOfWork();
        $groups = [];

        foreach (array_merge(
            $uow->getScheduledEntityInsertions(),
            $uow->getScheduledEntityUpdates(),
            $uow->getScheduledEntityDeletions()
        ) as $entity) {
            match(true) {
                $entity instanceof Client => $this->gymCache->invalidateTags(['clients_list']),
                $entity instanceof Booking => $this->gymCache->invalidateTags(["bookings_list_" . $entity->getClient()->getId(), "bookings_list_all"]),
                $entity instanceof MembershipPlan => [
                    $this->gymCache->invalidateTags(['membership_plans_list']),
                    $groups[] = 'membership',
                ],
                $entity instanceof Membership => $this->gymCache->invalidateTags(['memberships_list_' . $entity->getClient()->getId(), "memberships_list_all"]),
                $entity instanceof Payment => $this->gymCache->invalidateTags(['payments_list_' . $entity->getClient()->getId(), "payments_list_all"]),
                $entity instanceof Trainer => [
                    $this->gymCache->invalidateTags(['trainers_list']),
                    $groups[] = 'trainers',
                ],
                $entity instanceof TrainerWorkTime => [
                    $this->gymCache->invalidateTags(['trainer_worktimes_list_' . $entity->getTrainer()->getId(), 'trainer_worktimes_list_all']),
                    $groups[] = 'trainers',
                ],
                $entity instanceof Training => [
                    $this->gymCache->invalidateTags(['trainer_worktimes_list_' . $entity->getTrainerWorkTime()->getTrainer()->getId(), "trainings_list_all"]),
                ],
                $entity instanceof TrainingType => [
                    $this->gymCache->invalidateTags(['training_types_list']),
                    $groups[] = 'training',
                ],

                default => null
            };
        }

        $groupsUnique = array_unique($groups);
        foreach ($groupsUnique as $group) {
            $this->cacheVersionService->bump($group);
        }
    }
}
