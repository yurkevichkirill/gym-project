<?php

namespace App\DataFixtures;

use App\Booking\Entity\Booking;
use App\Membership\Entity\Membership;
use App\MembershipPlan\Entity\MembershipPlan;
use App\TrainerWorkTime\Entity\TrainerWorkTime;
use App\Training\Entity\Training;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        foreach ($manager->getRepository(MembershipPlan::class)->findAll() as $item) {
            $manager->remove($item);
        }

        foreach ($manager->getRepository(Training::class)->findAll() as $item) {
            $manager->remove($item);
        }

        foreach ($manager->getRepository(Booking::class)->findAll() as $item) {
            $manager->remove($item);
        }

        foreach ($manager->getRepository(Membership::class)->findAll() as $item) {
            $manager->remove($item);
        }

        foreach ($manager->getRepository(TrainerWorkTime::class)->findAll() as $item) {
            $manager->remove($item);
        }

        $manager->flush();
    }
}
