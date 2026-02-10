<?php

namespace App\DataFixtures;

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

        $client = new Client();
        $client->setFirstName("Kilian");
        $client->setLastName("Mbappe");
        $client->setEmail("mbappe@gmail.com");
        $client->setPhone("+34142342354235");
        $client->setBalance("100");
        $client->setAge(25);
        $client->setRoles(['ROLE_ADMIN']);
        $client->setPassword('$2y$13$Mp9L.CtbTiu7ga.NTfjtVOO6vhCLPDxyYenodaTLzZIpRx7OrMVBi');
        $manager->persist($client);

//        $trainerWorkTime = new TrainerWorkTime();
//        $trainerWorkTime->setTrainer($manager->getRepository(Trainer::class)->find(1));
//        $trainerWorkTime->setStartTime(new \DateTimeImmutable("10:00"));
//        $trainerWorkTime->setEndTime(new \DateTimeImmutable("18:00"));
//        $trainerWorkTime->setDate(new \DateTimeImmutable("10-02-2026"));
//        $manager->persist($trainerWorkTime);
//
//        $trainerWorkTime2 = new TrainerWorkTime();
//        $trainerWorkTime2->setTrainer($manager->getRepository(Trainer::class)->find(2));
//        $trainerWorkTime2->setStartTime(new \DateTimeImmutable("8:00"));
//        $trainerWorkTime2->setEndTime(new \DateTimeImmutable("20:00"));
//        $trainerWorkTime2->setDate(new \DateTimeImmutable("11-02-2026"));
//        $manager->persist($trainerWorkTime2);

//        $training = new Training();
//        $training->setTrainerWorkTime($manager->getRepository(TrainerWorkTime::class)->find(2));
//        $training->setStartTime(new \DateTimeImmutable("11:00"));
//        $training->setDurationMinutes(60);
//        $manager->persist($training);
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
