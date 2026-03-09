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
//        $trainingType->setName("Fitness");
//        $trainingType->setDescription("htwerdfahtesrhdbght");
//        $manager->persist($trainingType);
//
//        $trainingType = new TrainingType();
//        $trainingType->setName("Armwrestling");
//        $trainingType->setDescription("goooooood");
//        $manager->persist($trainingType);

//        $trainer = new Trainer();
//        $trainer->setPassword('$2y$13$AZRdOuh3hPh36DLKTzT/ouye24yo0Ks1V6NjSkBbFkFzopReD9aLG');
//        $trainer->setTrainingType($manager->getRepository(TrainingType::class)->find(1));
//        $trainer->setPricePerHour('50');
//        $trainer->setFirstName("Ronny");
//        $trainer->setLastName('Coleman');
//        $trainer->setPhone('+356547374376');
//        $trainer->setEmail("ronnycoleman@gmail.com");
//        $manager->persist($trainer);
//
//        $trainer = new Trainer();
//        $trainer->setPassword('$2y$13$7bWAa/e271z1Q30ebuFPHuQW5r4.WzurA45q7CqKDOb9UMs8It8QC');
//        $trainer->setTrainingType($manager->getRepository(TrainingType::class)->find(2));
//        $trainer->setPricePerHour('10');
//        $trainer->setFirstName("Ivan");
//        $trainer->setLastName('Rylkow');
//        $trainer->setPhone('+375292281488');
//        $trainer->setEmail("sylentjooo@gmail.com");
//        $manager->persist($trainer);

//        $manager->getRepository(Trainer::class)->find(3)->setTrainingType($manager->getRepository(TrainingType::class)->find(3));
//
//        $manager->getRepository(Trainer::class)->find(4)->setTrainingType($manager->getRepository(TrainingType::class)->find(4));

//        $trainerWorkTime = new TrainerWorkTime();
//        $trainerWorkTime->setTrainer($manager->getRepository(Trainer::class)->find(3));
//        $trainerWorkTime->setStartTime(new \DateTimeImmutable("10:00"));
//        $trainerWorkTime->setEndTime(new \DateTimeImmutable("22:00"));
//        $trainerWorkTime->setDate(new \DateTimeImmutable("10-03-2026"));
//        $manager->persist($trainerWorkTime);
//
//        $trainerWorkTime = new TrainerWorkTime();
//        $trainerWorkTime->setTrainer($manager->getRepository(Trainer::class)->find(3));
//        $trainerWorkTime->setStartTime(new \DateTimeImmutable("10:00"));
//        $trainerWorkTime->setEndTime(new \DateTimeImmutable("22:00"));
//        $trainerWorkTime->setDate(new \DateTimeImmutable("11-03-2026"));
//        $manager->persist($trainerWorkTime);
//
//        $trainerWorkTime = new TrainerWorkTime();
//        $trainerWorkTime->setTrainer($manager->getRepository(Trainer::class)->find(4));
//        $trainerWorkTime->setStartTime(new \DateTimeImmutable("12:00"));
//        $trainerWorkTime->setEndTime(new \DateTimeImmutable("19:00"));
//        $trainerWorkTime->setDate(new \DateTimeImmutable("11-03-2026"));
//        $manager->persist($trainerWorkTime);
//
//        $trainerWorkTime = new TrainerWorkTime();
//        $trainerWorkTime->setTrainer($manager->getRepository(Trainer::class)->find(4));
//        $trainerWorkTime->setStartTime(new \DateTimeImmutable("12:00"));
//        $trainerWorkTime->setEndTime(new \DateTimeImmutable("20:00"));
//        $trainerWorkTime->setDate(new \DateTimeImmutable("12-03-2026"));
//        $manager->persist($trainerWorkTime);
//
//        $membership_plan = new MembershipPlan();
//        $membership_plan->setName("Month Unlimit");
//        $membership_plan->setPrice("100");
//        $membership_plan->setDurationDays(31);
//        $manager->persist($membership_plan);
//
//        $membership_plan3 = new MembershipPlan();
//        $membership_plan3->setName("Year Unlimit");
//        $membership_plan3->setPrice("653");
//        $membership_plan3->setDurationDays(366);
//        $manager->persist($membership_plan3);
//
//        $membership_plan1 = new MembershipPlan();
//        $membership_plan1->setName("6 visits");
//        $membership_plan1->setPrice("60");
//        $membership_plan1->setDurationDays(31);
//        $membership_plan1->setSessionLimit(6);
//        $manager->persist($membership_plan1);
//
        $membership_plan2 = new MembershipPlan();
        $membership_plan2->setName("50 visits");
        $membership_plan2->setPrice("500");
        $membership_plan2->setDurationDays(366);
        $membership_plan2->setSessionLimit(50);
        $manager->persist($membership_plan2);
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



//        $payment = new Payment();
//        $payment->setClient($manager->getRepository(Client::class)->find(5));
//        $payment->setCategory(PaymentCategoryEnum::MEMBERSHIP);
//        $payment->setAmount("100");
//        $manager->persist($payment);
//
//        $membership = new Membership();
//        $membership->setClient($manager->getRepository(Client::class)->find(5));
//        $membership->setPlan($manager->getRepository(MembershipPlan::class)->find(1));
//        $membership->setStartDate(new \DateTimeImmutable('2026-01-18'));
//        $membership->setEndDate(new \DateTimeImmutable('2026-02-20'));
//        $manager->persist($membership);

//        $manager->remove($manager->getRepository(Membership::class)->find(2));
//        $manager->getRepository(Client::class)->find(5)->setBalance("1000");
        $manager->flush();
    }
}
