<?php

namespace App\DataFixtures;

use App\Entity\Booking;
use App\Entity\Client;
use App\Entity\Membership;
use App\Entity\MembershipPlan;
use App\Entity\Payment;
use App\Entity\Trainer;
use App\Entity\TrainerAvailability;
use App\Entity\Training;
use App\Enum\DayOfWeekEnum;
use App\Enum\PaymentCategoryEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $booking2 = new Booking();
        $booking2->setClient($manager->getRepository(Client::class)->find(2));
        $booking2->setTraining($manager->getRepository(Training::class)->find(2));
        $manager->persist($booking2);

        $manager->flush();
    }
}
