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
use App\Trainer\Service\TrainerManager;
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
//        $client->setFirstName("Veronika");
//        $client->setLastName("Loving");
//        $client->setEmail("veronika2@gmail.com");
//        $client->setPhone("+36326574675");
//        $client->setBalance("499");
//        $client->setAge(17);
//        $client->setPassword('$2y$13$0/iZHd/aJHWZgiUlE4.JSul5NjEZZSvXQuUgAJcJShUnXY5Qz7b7m');
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
//        $admin->setPassword('$2y$13$eLimavuJKRqcSolTR5ci7OjvWGrQaXediepuvOqw3jbnewCZ6inxK');
//        $manager->persist($admin);

//        $trainingType = new TrainingType();
//        $trainingType->setName("Powerlifting");
//        $trainingType->setDescription("Bodybuilding is a form of physical training focused on developing muscle size, strength, and symmetry through resistance exercises. It typically involves structured workouts targeting specific muscle groups, such as chest, back, legs, and arms, using weights or machines. Athletes follow carefully planned routines, combining progressive overload, proper technique, and recovery. Nutrition plays a crucial role, emphasizing high protein intake, balanced macronutrients, and adequate hydration to support muscle growth and repair. Bodybuilding can be practiced recreationally for fitness and aesthetics or competitively, where participants are judged on muscular definition, proportion, and presentation. Consistency, discipline, and goal-setting are key elements of success in this training style.");
//        $manager->persist($trainingType);
//
//        $trainingType = new TrainingType();
//        $trainingType->setName("Crossfit");
//        $trainingType->setDescription("Bodybuilding is a form of physical training focused on developing muscle size, strength, and symmetry through resistance exercises. It typically involves structured workouts targeting specific muscle groups, such as chest, back, legs, and arms, using weights or machines. Athletes follow carefully planned routines, combining progressive overload, proper technique, and recovery. Nutrition plays a crucial role, emphasizing high protein intake, balanced macronutrients, and adequate hydration to support muscle growth and repair. Bodybuilding can be practiced recreationally for fitness and aesthetics or competitively, where participants are judged on muscular definition, proportion, and presentation. Consistency, discipline, and goal-setting are key elements of success in this training style.");
//        $manager->persist($trainingType);
//
//        $trainingType = new TrainingType();
//        $trainingType->setName("Joga");
//        $trainingType->setDescription("Bodybuilding is a form of physical training focused on developing muscle size, strength, and symmetry through resistance exercises. It typically involves structured workouts targeting specific muscle groups, such as chest, back, legs, and arms, using weights or machines. Athletes follow carefully planned routines, combining progressive overload, proper technique, and recovery. Nutrition plays a crucial role, emphasizing high protein intake, balanced macronutrients, and adequate hydration to support muscle growth and repair. Bodybuilding can be practiced recreationally for fitness and aesthetics or competitively, where participants are judged on muscular definition, proportion, and presentation. Consistency, discipline, and goal-setting are key elements of success in this training style.");
//        $manager->persist($trainingType);
//
//        $trainingType = new TrainingType();
//        $trainingType->setName("Box");
//        $trainingType->setDescription("Bodybuilding is a form of physical training focused on developing muscle size, strength, and symmetry through resistance exercises. It typically involves structured workouts targeting specific muscle groups, such as chest, back, legs, and arms, using weights or machines. Athletes follow carefully planned routines, combining progressive overload, proper technique, and recovery. Nutrition plays a crucial role, emphasizing high protein intake, balanced macronutrients, and adequate hydration to support muscle growth and repair. Bodybuilding can be practiced recreationally for fitness and aesthetics or competitively, where participants are judged on muscular definition, proportion, and presentation. Consistency, discipline, and goal-setting are key elements of success in this training style.");
//        $trainingType->setPhotoUrl("http://nginx/uploads/training_types/box.jpg");
//        $manager->persist($trainingType);

//        $trainer = new TrainersListComponent();
//        $trainer->setPassword('$2y$13$AZRdOuh3hPh36DLKTzT/ouye24yo0Ks1V6NjSkBbFkFzopReD9aLG');
//        $trainer->setTrainingType($manager->getRepository(TrainingType::class)->find(1));
//        $trainer->setPricePerHour('50');
//        $trainer->setFirstName("Ronny");
//        $trainer->setLastName('Coleman');
//        $trainer->setPhone('+356547374376');
//        $trainer->setEmail("ronnycoleman@gmail.com");
//        $manager->persist($trainer);
//
//        $trainer = new OurTrainer();
//        $trainer->setPassword('$2y$13$fBRTPMrtZWQ3HxgOPQRPE.wRqqgQSDuqr7jS2yZRw0W8m/dsjCrK6');
//        $trainer->setTrainingType($manager->getRepository(TrainingType::class)->find(1));
//        $trainer->setPricePerHour('100');
//        $trainer->setFirstName("Maxim");
//        $trainer->setLastName('Donchenko');
//        $trainer->setPhone('+395689302556');
//        $trainer->setEmail("antitrainer@gmail.com");
//        $manager->persist($trainer);
//
//        $trainer = new OurTrainer();
//        $trainer->setPassword('$2y$13$bgyRBn49o3/LpdR0gIPIe.ZHL4P7JAjRl.6.IIqqqtuSXzx0Nrb2O');
//        $trainer->setTrainingType($manager->getRepository(TrainingType::class)->find(3));
//        $trainer->setPricePerHour('69');
//        $trainer->setFirstName("Egor");
//        $trainer->setLastName('Harchenko');
//        $trainer->setPhone('+734986983496');
//        $trainer->setEmail("minipekka@gmail.com");
//        $manager->persist($trainer);
//
//        $trainer = new Trainer();
//        $trainer->setPassword('$2y$13$mtp4ePCsWZocUEkmjqbmv.ohR7dhktcjEliLQirHO3jlVXznpoeVm');
//        $trainer->setTrainingType($manager->getRepository(TrainingType::class)->find(5));
//        $trainer->setPricePerHour('1000');
//        $trainer->setFirstName("Arnold");
//        $trainer->setLastName('Schwarzenegger');
//        $trainer->setPhone('+903485902592');
//        $trainer->setEmail("arnold@gmail.com");
//        $trainer->setPhotoUrl("http://nginx/uploads/trainers/arnold.jpg");
//        $manager->persist($trainer);

//        $manager->getRepository(TrainersListComponent::class)->find(3)->setTrainingType($manager->getRepository(TrainingType::class)->find(3));
//
//        $manager->getRepository(TrainersListComponent::class)->find(4)->setTrainingType($manager->getRepository(TrainingType::class)->find(4));

//        $trainerWorkTime = new TrainerWorkTime();
//        $trainerWorkTime->setTrainer($manager->getRepository(TrainersListComponent::class)->find(3));
//        $trainerWorkTime->setStartTime(new \DateTimeImmutable("10:00"));
//        $trainerWorkTime->setEndTime(new \DateTimeImmutable("22:00"));
//        $trainerWorkTime->setDate(new \DateTimeImmutable("10-03-2026"));
//        $manager->persist($trainerWorkTime);
//
//        $trainerWorkTime = new TrainerWorkTime();
//        $trainerWorkTime->setTrainer($manager->getRepository(TrainersListComponent::class)->find(3));
//        $trainerWorkTime->setStartTime(new \DateTimeImmutable("10:00"));
//        $trainerWorkTime->setEndTime(new \DateTimeImmutable("22:00"));
//        $trainerWorkTime->setDate(new \DateTimeImmutable("11-03-2026"));
//        $manager->persist($trainerWorkTime);
//
//        $trainerWorkTime = new TrainerWorkTime();
//        $trainerWorkTime->setTrainer($manager->getRepository(TrainersListComponent::class)->find(4));
//        $trainerWorkTime->setStartTime(new \DateTimeImmutable("12:00"));
//        $trainerWorkTime->setEndTime(new \DateTimeImmutable("19:00"));
//        $trainerWorkTime->setDate(new \DateTimeImmutable("11-03-2026"));
//        $manager->persist($trainerWorkTime);
//
//        $trainerWorkTime = new TrainerWorkTime();
//        $trainerWorkTime->setTrainer($manager->getRepository(TrainersListComponent::class)->find(4));
//        $trainerWorkTime->setStartTime(new \DateTimeImmutable("12:00"));
//        $trainerWorkTime->setEndTime(new \DateTimeImmutable("20:00"));
//        $trainerWorkTime->setDate(new \DateTimeImmutable("12-03-2026"));
//        $manager->persist($trainerWorkTime);
//
//        $membership_plan = new MembershipPlan();
//        $membership_plan->setName("3 Month");
//        $membership_plan->setPrice("240");
//        $membership_plan->setDurationDays(93);
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
//        $membership_plan2 = new MembershipPlan();
//        $membership_plan2->setName("50 visits");
//        $membership_plan2->setPrice("500");
//        $membership_plan2->setDurationDays(366);
//        $membership_plan2->setSessionLimit(50);
//        $manager->persist($membership_plan2);
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

//        $manager->getRepository(Trainer::class)->find(3)->setPhotoUrl("http://nginx/uploads/trainers/ronny.jpg");
//        $manager->getRepository(Trainer::class)->find(4)->setPhotoUrl("http://nginx/uploads/trainers/ivan.png");
//        $manager->getRepository(Trainer::class)->find(8)->setPhotoUrl("http://nginx/uploads/trainers/antitrainer.jpg");
//        $manager->getRepository(Trainer::class)->find(9)->setPhotoUrl("http://nginx/uploads/trainers/minipekka.jpg");
//        $manager->getRepository(Trainer::class)->find(10)->setPhotoUrl("http://nginx/uploads/trainers/vadimsapog.png");
//        $manager->getRepository(Trainer::class)->find(11)->setPhotoUrl("http://nginx/uploads/trainers/zyzz.jpg");
//        $manager->getRepository(Trainer::class)->find(4)->setPhotoUrl("http://localhost/uploads/trainers/ivan.png");
//        $manager->getRepository(OurTrainer::class)->find(4)->setTrainingType($manager->getRepository(TrainingType::class)->find(5));

//        $manager->getRepository(Trainer::class)->find(10)->setPricePerHour(52);
//        $manager->getRepository(Trainer::class)->find(3)->setPricePerHour("60");
//        $manager->getRepository(TrainingType::class)->find(3)->setDescription("Bodybuilding is a form of physical training focused on developing muscle size, strength, and symmetry through resistance exercises. It typically involves structured workouts targeting specific muscle groups, such as chest, back, legs, and arms, using weights or machines. Athletes follow carefully planned routines, combining progressive overload, proper technique, and recovery. Nutrition plays a crucial role, emphasizing high protein intake, balanced macronutrients, and adequate hydration to support muscle growth and repair. Bodybuilding can be practiced recreationally for fitness and aesthetics or competitively, where participants are judged on muscular definition, proportion, and presentation. Consistency, discipline, and goal-setting are key elements of success in this training style.");
//        $manager->getRepository(TrainingType::class)->find(4)->setDescription("Bodybuilding is a form of physical training focused on developing muscle size, strength, and symmetry through resistance exercises. It typically involves structured workouts targeting specific muscle groups, such as chest, back, legs, and arms, using weights or machines. Athletes follow carefully planned routines, combining progressive overload, proper technique, and recovery. Nutrition plays a crucial role, emphasizing high protein intake, balanced macronutrients, and adequate hydration to support muscle growth and repair. Bodybuilding can be practiced recreationally for fitness and aesthetics or competitively, where participants are judged on muscular definition, proportion, and presentation. Consistency, discipline, and goal-setting are key elements of success in this training style.");
//        $manager->getRepository(TrainingType::class)->find(5)->setDescription("Bodybuilding is a form of physical training focused on developing muscle size, strength, and symmetry through resistance exercises. It typically involves structured workouts targeting specific muscle groups, such as chest, back, legs, and arms, using weights or machines. Athletes follow carefully planned routines, combining progressive overload, proper technique, and recovery. Nutrition plays a crucial role, emphasizing high protein intake, balanced macronutrients, and adequate hydration to support muscle growth and repair. Bodybuilding can be practiced recreationally for fitness and aesthetics or competitively, where participants are judged on muscular definition, proportion, and presentation. Consistency, discipline, and goal-setting are key elements of success in this training style.");
//        $manager->getRepository(TrainingType::class)->find(8)->setDescription("Bodybuilding is a form of physical training focused on developing muscle size, strength, and symmetry through resistance exercises. It typically involves structured workouts targeting specific muscle groups, such as chest, back, legs, and arms, using weights or machines. Athletes follow carefully planned routines, combining progressive overload, proper technique, and recovery. Nutrition plays a crucial role, emphasizing high protein intake, balanced macronutrients, and adequate hydration to support muscle growth and repair. Bodybuilding can be practiced recreationally for fitness and aesthetics or competitively, where participants are judged on muscular definition, proportion, and presentation. Consistency, discipline, and goal-setting are key elements of success in this training style.");
//        $manager->getRepository(TrainingType::class)->find(3)->setPhotoUrl("http://nginx/uploads/trainers/zyzz.jpg");
//        $manager->getRepository(TrainingType::class)->find(3)->setPhotoUrl("http://nginx/uploads/training_types/bodybuilding.jpg");
//        $manager->getRepository(TrainingType::class)->find(4)->setPhotoUrl("http://nginx/uploads/training_types/armwrestling.jpg");
//        $manager->getRepository(TrainingType::class)->find(5)->setPhotoUrl("http://nginx/uploads/training_types/fitness.jpg");
//        $manager->getRepository(TrainingType::class)->find(8)->setPhotoUrl("http://nginx/uploads/training_types/pilates.jpg");
//        $manager->getRepository(TrainingType::class)->find(11)->setPhotoUrl("http://nginx/uploads/training_types/powerlifting.jpg");
//        $manager->getRepository(TrainingType::class)->find(12)->setPhotoUrl("http://nginx/uploads/training_types/crossfit.jpg");
//        $manager->getRepository(TrainingType::class)->find(13)->setName("Yoga");
//        $manager->remove($manager->getRepository(TrainingType::class)->find(14));
//        $manager->remove($manager->getRepository(TrainingType::class)->find(15));
//        $manager->remove($manager->getRepository(TrainingType::class)->find(16));
//        $manager->getRepository(Trainer::class)->find(3)->setEducation("Brest State A.S. Pushkin University");
//        $manager->getRepository(Trainer::class)->find(3)->setAbout("Experiense more than 20 years, mr Olimpia");
//
//        $manager->getRepository(Trainer::class)->find(4)->setEducation("Belarusian State University of Informatics and Radioelectronics");
//        $manager->getRepository(Trainer::class)->find(4)->setAbout("Mr Kartoshka");
//
//        $manager->getRepository(Trainer::class)->find(8)->setEducation("Grafit courses");
//        $manager->getRepository(Trainer::class)->find(8)->setAbout("Experience 5 year in dance and bodybuilding");
//
//        $manager->getRepository(Trainer::class)->find(9)->setEducation("9 classes at school");
//        $manager->getRepository(Trainer::class)->find(9)->setAbout("Tik Tok, youtube bloger");
//
//        $manager->getRepository(Trainer::class)->find(10)->setEducation("Doctor of Philosophy Stanford University");
//        $manager->getRepository(Trainer::class)->find(10)->setAbout("Tik Tok, youtube bloger");
//
//        $manager->getRepository(Trainer::class)->find(11)->setEducation("Lyceum 1 Brest");
//        $manager->getRepository(Trainer::class)->find(11)->setAbout("Aestetic founder");
//
//        $manager->getRepository(Trainer::class)->find(13)->setEducation("University of Wisconsin-Superior");
//        $manager->getRepository(Trainer::class)->find(13)->setAbout("First mr Olimpia, California governor");
//        $memberships = $manager->getRepository(Membership::class)->findAll();
//        $manager->remove($manager->getRepository(Membership::class)->find(1));
//        $manager->remove($manager->getRepository(Membership::class)->find(3));
//        $manager->remove($manager->getRepository(Membership::class)->find(5));
//        foreach ($manager->getRepository(Booking::class)->findAll() as $item) {
//            $manager->remove($manager->getRepository(Booking::class)->find($item->getId()));
//        }
//
//        foreach ($manager->getRepository(Membership::class)->findAll() as $item) {
//            $manager->remove($manager->getRepository(Membership::class)->find($item->getId()));
//        }
//
//        foreach ($manager->getRepository(Payment::class)->findAll() as $item) {
//            $manager->remove($manager->getRepository(Payment::class)->find($item->getId()));
//        }
//        $manager->remove($manager->getRepository(Booking::class)->find(12));

//        $manager->getRepository(Client::class)->find(5)->setBalance(1000);
//        $manager->remove($manager->getRepository(Booking::class)->find(29));
//        $manager->remove($manager->getRepository(Booking::class)->find(31));
//        $manager->remove($manager->getRepository(Booking::class)->find(32));

        $manager->getRepository(Client::class)->find(5)->setBalance("99999");
        $manager->flush();
    }
}
