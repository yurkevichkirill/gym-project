<?php

namespace App\DataFixtures;

use App\Admin\Entity\Admin;
use App\Booking\Entity\Booking;
use App\Booking\Enum\BookingStatusEnum;
use App\Client\Entity\Client;
use App\ImportError\Entity\ImportError;
use App\ImportJob\Entity\ImportJob;
use App\Membership\Entity\Membership;
use App\MembershipPlan\Entity\MembershipPlan;
use App\Payment\Entity\Payment;
use App\Payment\Enum\PaymentCategoryEnum;
use App\Payment\Enum\PaymentStatusEnum;
use App\Payment\Repository\PaymentRepository;
use App\Trainer\Entity\Trainer;
use App\Trainer\Service\TrainerManager;
use App\TrainerWorkTime\Entity\TrainerWorkTime;
use App\Training\Entity\Training;
use App\TrainingType\Entity\TrainingType;
use App\User\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
//        $admin = new Admin();
//        $admin->setFirstName('Kirill');
//        $admin->setLastName('Yurkevich');
//        $admin->setEmail('yurkevichkirill@gmail.com');
//        $admin->setPhone('+36326574675');
//        $admin->setPassword('$2y$13$whI9GwJBWgiRhKYzK8bsbevmyIbIij9XoREgqNQ11gb3WSqqYWQOW');
//        $manager->persist($admin);
//
//        $clientRonaldo = new Client();
//        $clientRonaldo->setFirstName('Cristiano');
//        $clientRonaldo->setLastName('Ronaldo');
//        $clientRonaldo->setEmail('ronaldo@gmail.com');
//        $clientRonaldo->setPhone('+675836768');
//        $clientRonaldo->setAge(40);
//        $clientRonaldo->setPassword('$2y$13$ng0o3lgTjZqkEZ2Ajry03eQs/5ZyvvoXtPbI/JOe38XolA2ls0mqG');
//        $manager->persist($clientRonaldo);
//
//        $clientMbappe = new Client();
//        $clientMbappe->setFirstName('Kylian');
//        $clientMbappe->setLastName('Mbappe');
//        $clientMbappe->setEmail('mbappe@gmail.com');
//        $clientMbappe->setPhone('+89765867985978');
//        $clientMbappe->setAge(27);
//        $clientMbappe->setPassword('$2y$13$DnKNyyIow9y6azrMejq6AOZd5XmE912AZc4V2hbhvXdeRPOH1h5B6');
//        $manager->persist($clientMbappe);
//
//        $clientVinisius = new Client();
//        $clientVinisius->setFirstName('Vinisius');
//        $clientVinisius->setLastName('Junior');
//        $clientVinisius->setEmail('vinisius@gmail.com');
//        $clientVinisius->setPhone('+235678579680756');
//        $clientVinisius->setAge(14);
//        $clientVinisius->setPassword('$2y$13$ZcKZ2mVxyfjwA/LG40x1qe.L5APXoyKDgltVj8791L7OsdrW2s/em');
//        $manager->persist($clientVinisius);
//
//        $trainingTypeArm = new TrainingType();
//        $trainingTypeArm->setName('Armwrestling');
//        $trainingTypeArm->setDescription('Armwrestling');
//        $trainingTypeArm->setPhotoUrl('http://nginx/uploads/training_types/armwrestling.jpg');
//        $manager->persist($trainingTypeArm);
//
//        $trainingTypeBodybuilding = new TrainingType();
//        $trainingTypeBodybuilding->setName('Bodybuilding');
//        $trainingTypeBodybuilding->setDescription('Bodybuilding');
//        $trainingTypeBodybuilding->setPhotoUrl('http://nginx/uploads/training_types/bodybuilding.jpg');
//        $manager->persist($trainingTypeBodybuilding);
//
//        $trainingTypeBox = new TrainingType();
//        $trainingTypeBox->setName("Box");
//        $trainingTypeBox->setDescription('Box');
//        $trainingTypeBox->setPhotoUrl("http://nginx/uploads/training_types/box.jpg");
//        $manager->persist($trainingTypeBox);
//
//        $trainerColeman = new Trainer();
//        $trainerColeman->setPassword('$2y$13$pTDJANvKmRNs3cXTEwmqpuXCD/SZDNlcCj1TG52shp.79c.rhitQO');
//        $trainerColeman->setTrainingType($trainingTypeBodybuilding);
//        $trainerColeman->setPricePerHour(5000);
//        $trainerColeman->setFirstName('Ronny');
//        $trainerColeman->setLastName('Coleman');
//        $trainerColeman->setPhone('+356547374376');
//        $trainerColeman->setEmail('coleman@gmail.com');
//        $trainerColeman->setPhotoUrl('http://nginx/uploads/trainers/ronny.jpg');
//        $manager->persist($trainerColeman);
//
//        $trainerLarrat = new Trainer();
//        $trainerLarrat->setPassword('$2y$13$Wf0R0C2prj81lNSPqRmPAu1wJ4oGfImcHJC6RNRTqPYqcj41ZLfk2');
//        $trainerLarrat->setTrainingType($trainingTypeArm);
//        $trainerLarrat->setPricePerHour(4500);
//        $trainerLarrat->setFirstName('Devon');
//        $trainerLarrat->setLastName('Larrat');
//        $trainerLarrat->setPhone('+395689302556');
//        $trainerLarrat->setEmail('larrat@gmail.com');
//        $trainerLarrat->setPhotoUrl('http://nginx/uploads/trainers/larrat.jpg');
//        $manager->persist($trainerLarrat);
//
//        $trainerTyson = new Trainer();
//        $trainerTyson->setPassword('$2y$13$OQtVs/8.Hh/G1/NGA9HKoOOEn5/iqoIyX5DlIfbP6TWRx04c3jCVO');
//        $trainerTyson->setTrainingType($trainingTypeBox);
//        $trainerTyson->setPricePerHour(3000);
//        $trainerTyson->setFirstName('Mike');
//        $trainerTyson->setLastName('Tyson');
//        $trainerTyson->setPhone('+734986983496');
//        $trainerTyson->setEmail('tyson@gmail.com');
//        $trainerTyson->setPhotoUrl("http://nginx/uploads/trainers/tyson.jpg");
//        $manager->persist($trainerTyson);
//
//        $trainerArnold = new Trainer();
//        $trainerArnold->setPassword('$2y$13$oA72kyWWkDJxHxNtxlmYMuS/XCVaKaUa7Vr09goDEV8HoCXMUBPvK');
//        $trainerArnold->setTrainingType($trainingTypeBodybuilding);
//        $trainerArnold->setPricePerHour(10000);
//        $trainerArnold->setFirstName("Arnold");
//        $trainerArnold->setLastName('Schwarzenegger');
//        $trainerArnold->setPhone('+903485902592');
//        $trainerArnold->setEmail("arnold@gmail.com");
//        $trainerArnold->setPhotoUrl("http://nginx/uploads/trainers/arnold.jpg");
//        $manager->persist($trainerArnold);
//
//        $trainerWorkTime1 = new TrainerWorkTime();
//        $trainerWorkTime1->setTrainer($trainerColeman);
//        $trainerWorkTime1->setStartTime(new \DateTimeImmutable("10:00:00"));
//        $trainerWorkTime1->setEndTime(new \DateTimeImmutable("23:00:00"));
//        $trainerWorkTime1->setDate(new \DateTimeImmutable("2026-06-20"));
//        $manager->persist($trainerWorkTime1);
//
//        $trainerWorkTime2 = new TrainerWorkTime();
//        $trainerWorkTime2->setTrainer($trainerColeman);
//        $trainerWorkTime2->setStartTime(new \DateTimeImmutable("10:00:00"));
//        $trainerWorkTime2->setEndTime(new \DateTimeImmutable("23:00:00"));
//        $trainerWorkTime2->setDate(new \DateTimeImmutable("2026-06-21"));
//        $manager->persist($trainerWorkTime2);
//
//        $trainerWorkTime3 = new TrainerWorkTime();
//        $trainerWorkTime3->setTrainer($trainerLarrat);
//        $trainerWorkTime3->setStartTime(new \DateTimeImmutable("10:00:00"));
//        $trainerWorkTime3->setEndTime(new \DateTimeImmutable("23:00:00"));
//        $trainerWorkTime3->setDate(new \DateTimeImmutable("2026-07-20"));
//        $manager->persist($trainerWorkTime3);
//
//        $trainerWorkTime4 = new TrainerWorkTime();
//        $trainerWorkTime4->setTrainer($trainerLarrat);
//        $trainerWorkTime4->setStartTime(new \DateTimeImmutable("10:00:00"));
//        $trainerWorkTime4->setEndTime(new \DateTimeImmutable("23:00:00"));
//        $trainerWorkTime4->setDate(new \DateTimeImmutable("2026-07-21"));
//        $manager->persist($trainerWorkTime4);
//
//        $trainerWorkTime5 = new TrainerWorkTime();
//        $trainerWorkTime5->setTrainer($trainerTyson);
//        $trainerWorkTime5->setStartTime(new \DateTimeImmutable("10:00:00"));
//        $trainerWorkTime5->setEndTime(new \DateTimeImmutable("23:00:00"));
//        $trainerWorkTime5->setDate(new \DateTimeImmutable("2026-08-20"));
//        $manager->persist($trainerWorkTime5);
//
//        $trainerWorkTime6 = new TrainerWorkTime();
//        $trainerWorkTime6->setTrainer($trainerTyson);
//        $trainerWorkTime6->setStartTime(new \DateTimeImmutable("10:00:00"));
//        $trainerWorkTime6->setEndTime(new \DateTimeImmutable("23:00:00"));
//        $trainerWorkTime6->setDate(new \DateTimeImmutable("2026-08-21"));
//        $manager->persist($trainerWorkTime6);
//
//        $trainerWorkTime7 = new TrainerWorkTime();
//        $trainerWorkTime7->setTrainer($trainerArnold);
//        $trainerWorkTime7->setStartTime(new \DateTimeImmutable("10:00:00"));
//        $trainerWorkTime7->setEndTime(new \DateTimeImmutable("23:00:00"));
//        $trainerWorkTime7->setDate(new \DateTimeImmutable("2026-09-20"));
//        $manager->persist($trainerWorkTime7);
//
//        $trainerWorkTime8 = new TrainerWorkTime();
//        $trainerWorkTime8->setTrainer($trainerArnold);
//        $trainerWorkTime8->setStartTime(new \DateTimeImmutable("10:00:00"));
//        $trainerWorkTime8->setEndTime(new \DateTimeImmutable("23:00:00"));
//        $trainerWorkTime8->setDate(new \DateTimeImmutable("2026-09-21"));
//        $manager->persist($trainerWorkTime8);
//
//        $membershipPlan10Visits = new MembershipPlan();
//        $membershipPlan10Visits->setName("10 visits");
//        $membershipPlan10Visits->setPrice(3000);
//        $membershipPlan10Visits->setDurationDays(31);
//        $membershipPlan10Visits->setSessionLimit(10);
//        $manager->persist($membershipPlan10Visits);
//
//        $membershipPlanMonth = new MembershipPlan();
//        $membershipPlanMonth->setName("Month Unlimit");
//        $membershipPlanMonth->setPrice(10000);
//        $membershipPlanMonth->setDurationDays(31);
//        $manager->persist($membershipPlanMonth);

        $membershipPlan2Month = new MembershipPlan();
        $membershipPlan2Month->setName("2 Month 20 visits");
        $membershipPlan2Month->setPrice(16000);
        $membershipPlan2Month->setDurationDays(62);
        $membershipPlan2Month->setSessionLimit(20);
        $manager->persist($membershipPlan2Month);
//
//        $membershipPlanYear = new MembershipPlan();
//        $membershipPlanYear->setName("Year Unlimit");
//        $membershipPlanYear->setPrice(30000);
//        $membershipPlanYear->setDurationDays(366);
//        $manager->persist($membershipPlanYear);

        $manager->flush();
    }
}
