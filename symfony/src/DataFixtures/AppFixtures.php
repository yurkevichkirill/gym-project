<?php

namespace App\DataFixtures;

use App\Booking\Entity\Booking;
use App\Client\Entity\Client;
use App\Training\Entity\Training;
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
