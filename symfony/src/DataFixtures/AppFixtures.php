<?php

namespace App\DataFixtures;

use App\Admin\Entity\Admin;
use App\Booking\Entity\Booking;
use App\Client\Entity\Client;
use App\Membership\Entity\Membership;
use App\MembershipPlan\Entity\MembershipPlan;
use App\Payment\Entity\Payment;
use App\Payment\Enum\PaymentCategoryEnum;
use App\Payment\Repository\PaymentRepository;
use App\Trainer\Entity\Trainer;
use App\TrainerWorkTime\Entity\TrainerWorkTime;
use App\Training\Entity\Training;
use App\TrainingType\Entity\TrainingType;
use App\User\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
//        $client = new Client();
//        $client->setFirstName("Slava");
//        $client->setLastName("Merlow");
//        $client->setEmail("slava_marlow@gmail.com");
//        $client->setPhone("+34142342354235");
//        $client->setBalance("100");
//        $client->setAge(25);
//        $client->setPassword('$2y$13$xn19jDhOYkY6AXH96H37KOO6KILDHrnu47IaRErPT2XZSlauYZXVS');
//        $manager->persist($client);
//
//        $client = new Client();
//        $client->setFirstName("Kirill");
//        $client->setLastName("Yurkevich");
//        $client->setEmail("kirillyurkevich@gmail.com");
//        $client->setPhone("+34142342354235");
//        $client->setBalance("100");
//        $client->setAge(25);
//        $client->setPassword('$2y$13$Mp9L.CtbTiu7ga.NTfjtVOO6vhCLPDxyYenodaTLzZIpRx7OrMVBi');
//        $manager->persist($client);

//        $admin = new Admin();
//        $admin->setFirstName("Kirill");
//        $admin->setLastName("Yurkevich");
//        $admin->setEmail("yurkevichkirill@gmail.com");
//        $admin->setPhone("+34142342354235");
//        $admin->setPassword('123456789');
//        $manager->persist($admin);

//        $trainingType = new TrainingType();
//        $trainingType->setName("Bodybuilding");
//        $trainingType->setDescription("rlkgnlrewnlgrewknewrg");
//        $manager->persist($trainingType);
//
//        $trainingType = new TrainingType();
//        $trainingType->setName("Armwrestling");
//        $trainingType->setDescription("goooooood");
//        $manager->persist($trainingType);

        $trainer = new Trainer();
        $trainer->setPassword('$2y$13$5ugBzeOUeK8d5Fv/PhkBcerPq7HPIpbdEN8hZyq5ufn9ozQ4wAG0u');
        $trainer->setTrainingType($manager->getRepository(TrainingType::class)->find(3));
        $trainer->setPrice('10');
        $trainer->setFirstName("Tima");
        $trainer->setLastName('Coleda');
        $trainer->setPhone('+0934796737');
        $trainer->setEmail("timakoleda@gmail.com");
        $manager->persist($trainer);

//        $trainerWorkTime = new TrainerWorkTime();
//        $trainerWorkTime->setTrainer($manager->getRepository(Trainer::class)->find(6));
//        $trainerWorkTime->setStartTime(new \DateTimeImmutable("10:00"));
//        $trainerWorkTime->setEndTime(new \DateTimeImmutable("22:00"));
//        $trainerWorkTime->setDate(new \DateTimeImmutable("10-03-2026"));
//        $manager->persist($trainerWorkTime);

//
//        for($i = 0; $i < 23; $i++) {
//            $booking = new Booking();
//            $booking->setClient($manager->getRepository(Client::class)->find(3));
//            $booking->setTraining($manager->getRepository(Training::class)->find($i + 6));
//            $manager->persist($booking);
//        }
//
//        $training1 = new Training();
//        $training1->setTrainerWorkTime($manager->getRepository(TrainerWorkTime::class)->find(2));
//        $training1->setStartTime(new \DateTimeImmutable("15:00"));
//        $training1->setDurationMinutes(120);
//        $manager->persist($training1);
//
//        $training2 = new Training();
//        $training2->setTrainerWorkTime($manager->getRepository(TrainerWorkTime::class)->find(3));
//        $training2->setStartTime(new \DateTimeImmutable("12:00"));
//        $training2->setDurationMinutes(120);
//        $manager->persist($training2);

//        $booking = new Booking();
//        $booking->setClient($manager->getRepository(Client::class)->find(2));
//        $booking->setTraining($manager->getRepository(Training::class)->find(2));
//        $manager->persist($booking);

//        $membership_plan = new MembershipPlan();
//        $membership_plan->setName("Month Unlimit");
//        $membership_plan->setPrice("100");
//        $membership_plan->setDurationDays(31);
//        $manager->persist($membership_plan);
//
//        $membership_plan1 = new MembershipPlan();
//        $membership_plan1->setName("6 visits");
//        $membership_plan1->setPrice("60");
//        $membership_plan1->setDurationDays(31);
//        $membership_plan1->setSessionLimit(6);
//        $manager->persist($membership_plan1);
//
//        $membership_plan2 = new MembershipPlan();
//        $membership_plan2->setName("8 visits");
//        $membership_plan2->setPrice("80");
//        $membership_plan2->setDurationDays(31);
//        $membership_plan2->setSessionLimit(8);
//        $manager->persist($membership_plan2);

//        $payment = new Payment();
//        $payment->setClient($manager->getRepository(Client::class)->find(5));
//        $payment->setCategory(PaymentCategoryEnum::MEMBERSHIP);
//        $payment->setAmount("100");
//        $manager->persist($payment);
//
//        $membership = new Membership();
//        $membership->setClient($manager->getRepository(Client::class)->find(5));
//        $membership->setPlan($manager->getRepository(MembershipPlan::class)->find(3));
//        $manager->persist($membership);

        $manager->flush();
    }
}
