<?php

namespace App\DataFixtures;

use App\Booking\Entity\Booking;
use App\Client\Entity\Client;
use App\Trainer\Entity\Trainer;
use App\TrainerWorkTime\Entity\TrainerWorkTime;
use App\Training\Entity\Training;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $client = new Client();
        $client->setFirstName("Oleg");
        $client->setLastName("Ivanov");
        $client->setAge(22);
        $client->setBalance("15");
        $client->setEmail("olegivanov@gmail.com");
        $client->setPassword('$2y$13$gt6wiL20y2hAwqyOt5e2kOGzYwcF96YK46pAxkP5SQv1qKtX.nOGa');
        $client->setPhone("+3543525426");
        $client->setRoles([]);
        $manager->persist($client);

        $manager->flush();
    }
}
