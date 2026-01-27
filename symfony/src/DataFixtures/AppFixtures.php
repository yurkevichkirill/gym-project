<?php

namespace App\DataFixtures;

use App\Entity\MembershipPlan;
use App\Entity\Trainer;
use App\Entity\TrainerAvailability;
use App\Entity\Training;
use App\Enum\DayOfWeekEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $membership1 = new MembershipPlan();
        $membership1->setDurationDays(30);
        $membership1->setName("Monthly Unlimit");
        $membership1->setPrice("100");
        $manager->persist($membership1);

        $membership2 = new MembershipPlan();
        $membership2->setDurationDays(30);
        $membership2->setName("4 visits");
        $membership2->setSessionLimit(4);
        $membership2->setPrice("60");
        $manager->persist($membership2);

        $manager->flush();
    }
}
