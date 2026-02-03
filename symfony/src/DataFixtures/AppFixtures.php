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
        $training3 = new Training();
        $training3->setTrainerWorkTime($manager->getRepository(TrainerWorkTime::class)->find(4));
        $training3->setStartTime(new \DateTimeImmutable("16:00"));
        $training3->setDurationMinutes(120);
        $manager->persist($training3);

        $manager->flush();
    }
}
