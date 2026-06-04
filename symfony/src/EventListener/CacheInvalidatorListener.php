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
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Events;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[AsDoctrineListener(event: Events::onFlush)]
#[AsDoctrineListener(event: Events::postFlush)]
final class CacheInvalidatorListener
{
    /** @var list<string> */
    private array $tagsToInvalidate = [];
    /** @var list<string> */
    private array $groupsToBump = [];

    public function __construct(
        private readonly TagAwareCacheInterface $cache,
        private readonly CacheVersionService    $cacheVersionService,
    ) {}

    public function onFlush(OnFlushEventArgs $eventArgs): void
    {
        $em = $eventArgs->getObjectManager();
        $uow = $em->getUnitOfWork();

        $entities = array_merge(
            $uow->getScheduledEntityInsertions(),
            $uow->getScheduledEntityUpdates(),
            $uow->getScheduledEntityDeletions()
        );

        foreach ($entities as $entity) {
            if ($entity instanceof Client) {
                $this->tagsToInvalidate[] = 'clients_list';
            }
            elseif ($entity instanceof Booking) {
                $this->tagsToInvalidate[] = "bookings_list_{$entity->getClient()->getId()}";
                $this->tagsToInvalidate[] = 'bookings_list_all';
            }
            elseif ($entity instanceof MembershipPlan) {
                $this->tagsToInvalidate[] = 'membership_plans_list';
                $this->groupsToBump[] = 'membership';
            }
            elseif ($entity instanceof Membership) {
                $this->tagsToInvalidate[] = "memberships_list_{$entity->getClient()->getId()}";
                $this->tagsToInvalidate[] = 'memberships_list_all';
            }
            elseif ($entity instanceof Payment) {
                $client = $entity->getClient();
                if ($client !== null) {
                    $this->tagsToInvalidate[] = "payments_list_{$client->getId()}";
                }
                $this->tagsToInvalidate[] = 'payments_list_all';

                $trainer = $entity->getTrainer();
                if ($trainer !== null) {
                    $this->tagsToInvalidate[] = "payments_list_trainer_{$trainer->getId()}";
                }
            }
            elseif ($entity instanceof Trainer) {
                $this->tagsToInvalidate[] = 'trainers_list';
                $this->tagsToInvalidate[] = 'trainers_list_public';
                $this->groupsToBump[] = 'trainers';
                $this->tagsToInvalidate[] = "trainer_worktimes_list_{$entity->getId()}";
                $this->tagsToInvalidate[] = 'trainer_worktimes_list_all';
                $this->groupsToBump[] = 'worktime';
            }
            elseif ($entity instanceof TrainerWorkTime) {
                $this->tagsToInvalidate[] = "trainer_worktimes_list_{$entity->getTrainer()->getId()}";
                $this->tagsToInvalidate[] = 'trainer_worktimes_list_all';
                $this->groupsToBump[] = 'trainers';
                $this->groupsToBump[] = 'worktime';
            }
            elseif ($entity instanceof Training) {
                $trainerId = $entity->getTrainerWorkTime()->getTrainer()->getId();
                $this->tagsToInvalidate[] = "trainer_worktimes_list_$trainerId";
                $this->tagsToInvalidate[] = 'trainer_worktimes_list_all';
                $this->tagsToInvalidate[] = "trainings_list_$trainerId";
                $this->tagsToInvalidate[] = 'trainings_list_all';
                $this->groupsToBump[] = 'worktime';
                $this->groupsToBump[] = 'training';
            }
            elseif ($entity instanceof TrainingType) {
                $this->tagsToInvalidate[] = 'training_types_list';
                $this->groupsToBump[] = 'training';
            }
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    public function postFlush(): void
    {
        if ($this->tagsToInvalidate !== []) {
            /** @var list<string> $tags */
            $tags = array_values(array_unique($this->tagsToInvalidate));
            $this->cache->invalidateTags($tags);
        }

        if ($this->groupsToBump !== []) {
            /** @var list<string> $groups */
            $groups = array_values(array_unique($this->groupsToBump));
            foreach ($groups as $group) {
                $this->cacheVersionService->bump($group);
            }
        }

        $this->tagsToInvalidate = [];
        $this->groupsToBump = [];
    }
}
