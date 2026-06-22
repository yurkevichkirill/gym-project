<?php

namespace App\DataFixtures;

use App\Admin\Entity\Admin;
use App\Booking\Entity\Booking;
use App\Client\Entity\Client;
use App\Membership\Entity\Membership;
use App\MembershipPlan\Entity\MembershipPlan;
use App\Trainer\Entity\Trainer;
use App\TrainerWorkTime\Entity\TrainerWorkTime;
use App\Training\Entity\Training;
use App\TrainingType\Entity\TrainingType;
use App\User\Entity\User;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AppFixtures extends Fixture
{
    private const TEST_PASSWORD = 'password';

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
//        $admin = $this->admin($manager, [
//            'firstName' => 'Kirill',
//            'lastName' => 'Yurkevich',
//            'email' => 'admin@gym.test',
//            'phone' => '+375291000001',
//        ]);
//
//        $clients = [
//            $this->client($manager, [
//                'firstName' => 'Cristiano',
//                'lastName' => 'Ronaldo',
//                'email' => 'ronaldo@gym.test',
//                'phone' => '+375291000002',
//                'age' => 40,
//                'balance' => 15000,
//            ]),
//            $this->client($manager, [
//                'firstName' => 'Kylian',
//                'lastName' => 'Mbappe',
//                'email' => 'mbappe@gym.test',
//                'phone' => '+375291000003',
//                'age' => 27,
//                'balance' => 8000,
//            ]),
//            $this->client($manager, [
//                'firstName' => 'Vinisius',
//                'lastName' => 'Junior',
//                'email' => 'vinisius@gym.test',
//                'phone' => '+375291000004',
//                'age' => 24,
//                'balance' => 0,
//            ]),
//        ];
//
//        $trainingTypes = [
//            'armwrestling' => $this->trainingType($manager, [
//                'name' => 'Armwrestling',
//                'description' => 'Strength and technique training for armwrestling.',
//                'photoPath' => null,
//            ]),
//            'bodybuilding' => $this->trainingType($manager, [
//                'name' => 'Bodybuilding',
//                'description' => 'Muscle growth, strength work and gym conditioning.',
//                'photoPath' => null,
//            ]),
//            'boxing' => $this->trainingType($manager, [
//                'name' => 'Boxing',
//                'description' => 'Boxing skills, footwork, bag work and conditioning.',
//                'photoPath' => null,
//            ]),
//            'yoga' => $this->trainingType($manager, [
//                'name' => 'Yoga',
//                'description' => 'Mobility, stretching, breathing and recovery practice.',
//                'photoPath' => null,
//            ]),
//        ];
//
//        $trainers = [
//            $this->trainer($manager, [
//                'firstName' => 'Ronnie',
//                'lastName' => 'Coleman',
//                'email' => 'coleman@gym.test',
//                'phone' => '+375291000005',
//                'pricePerHour' => 5000,
//                'balance' => 0,
//                'education' => 'Certified strength and bodybuilding coach.',
//                'about' => 'Helps clients build strength, technique and discipline.',
//                'photoPath' => null,
//                'trainingType' => $trainingTypes['bodybuilding'],
//            ]),
//            $this->trainer($manager, [
//                'firstName' => 'Devon',
//                'lastName' => 'Larratt',
//                'email' => 'larratt@gym.test',
//                'phone' => '+375291000006',
//                'pricePerHour' => 4500,
//                'balance' => 0,
//                'education' => 'Professional armwrestling coach.',
//                'about' => 'Focuses on arm strength, grip work and match strategy.',
//                'photoPath' => null,
//                'trainingType' => $trainingTypes['armwrestling'],
//            ]),
//            $this->trainer($manager, [
//                'firstName' => 'Mike',
//                'lastName' => 'Tyson',
//                'email' => 'tyson@gym.test',
//                'phone' => '+375291000007',
//                'pricePerHour' => 3000,
//                'balance' => 0,
//                'education' => 'Boxing fundamentals and conditioning coach.',
//                'about' => 'Works with striking technique, endurance and coordination.',
//                'photoPath' => null,
//                'trainingType' => $trainingTypes['boxing'],
//            ]),
//            $this->trainer($manager, [
//                'firstName' => 'Anna',
//                'lastName' => 'Ivanova',
//                'email' => 'ivanova@gym.test',
//                'phone' => '+375291000008',
//                'pricePerHour' => 2500,
//                'balance' => 0,
//                'education' => 'Yoga instructor and mobility specialist.',
//                'about' => 'Builds recovery sessions for beginners and active athletes.',
//                'photoPath' => null,
//                'trainingType' => $trainingTypes['yoga'],
//            ]),
//        ];
//
//        $this->workTime($manager, $trainers[0], '2026-06-20', '10:00:00', '18:00:00');
//        $this->workTime($manager, $trainers[0], '2026-06-21', '12:00:00', '20:00:00');
//        $this->workTime($manager, $trainers[1], '2026-06-20', '09:00:00', '17:00:00');
//        $this->workTime($manager, $trainers[1], '2026-06-22', '10:00:00', '16:00:00');
//        $this->workTime($manager, $trainers[2], '2026-06-23', '11:00:00', '19:00:00');
//        $this->workTime($manager, $trainers[2], '2026-06-24', '10:00:00', '18:00:00');
//        $this->workTime($manager, $trainers[3], '2026-06-25', '08:00:00', '14:00:00');
//        $this->workTime($manager, $trainers[3], '2026-06-26', '14:00:00', '20:00:00');
//
//        $this->membershipPlan($manager, [
//            'name' => 'Single visit',
//            'price' => 700,
//            'durationDays' => 1,
//            'sessionLimit' => 1,
//        ]);
//        $this->membershipPlan($manager, [
//            'name' => '10 visits',
//            'price' => 3000,
//            'durationDays' => 31,
//            'sessionLimit' => 10,
//        ]);
//        $this->membershipPlan($manager, [
//            'name' => 'Month Unlimited',
//            'price' => 10000,
//            'durationDays' => 31,
//            'sessionLimit' => null,
//        ]);
//        $this->membershipPlan($manager, [
//            'name' => 'Year Unlimited',
//            'price' => 30000,
//            'durationDays' => 366,
//            'sessionLimit' => null,
//        ]);
//
//        $manager->persist($admin);
//        foreach ($clients as $client) {
//            $manager->persist($client);
//        }
        foreach ($manager->getRepository(MembershipPlan::class)->findAll() as $item) {
            $manager->remove($item);
        }

        foreach ($manager->getRepository(Training::class)->findAll() as $item) {
            $manager->remove($item);
        }

        foreach ($manager->getRepository(Booking::class)->findAll() as $item) {
            $manager->remove($item);
        }

        foreach ($manager->getRepository(Membership::class)->findAll() as $item) {
            $manager->remove($item);
        }

        foreach ($manager->getRepository(TrainerWorkTime::class)->findAll() as $item) {
            $manager->remove($item);
        }

        $manager->flush();
    }

    /**
     * @param array{firstName: string, lastName: string, email: string, phone: string} $data
     */
    private function admin(ObjectManager $manager, array $data): Admin
    {
        $admin = $manager->getRepository(Admin::class)->findOneBy(['email' => $data['email']]) ?? new Admin();

        $this->fillUser($admin, $data);
        $manager->persist($admin);

        return $admin;
    }

    /**
     * @param array{firstName: string, lastName: string, email: string, phone: string, age: int, balance: int} $data
     */
    private function client(ObjectManager $manager, array $data): Client
    {
        $client = $manager->getRepository(Client::class)->findOneBy(['email' => $data['email']]) ?? new Client();

        $this->fillUser($client, $data);
        $client->setAge($data['age']);
        $client->setBalance($data['balance']);
        $manager->persist($client);

        return $client;
    }

    /**
     * @param array{firstName: string, lastName: string, email: string, phone: string, pricePerHour: int, balance: int, education: ?string, about: ?string, photoPath: ?string, trainingType: TrainingType} $data
     */
    private function trainer(ObjectManager $manager, array $data): Trainer
    {
        $trainer = $manager->getRepository(Trainer::class)->findOneBy(['email' => $data['email']]) ?? new Trainer();

        $this->fillUser($trainer, $data);
        $trainer->setPricePerHour($data['pricePerHour']);
        $trainer->setBalance($data['balance']);
        $trainer->setEducation($data['education']);
        $trainer->setAbout($data['about']);
        $trainer->setPhotoPath($data['photoPath']);
        $trainer->setTrainingType($data['trainingType']);
        $manager->persist($trainer);

        return $trainer;
    }

    /**
     * @param array{firstName: string, lastName: string, email: string, phone: string} $data
     */
    private function fillUser(User $user, array $data): void
    {
        $user->setFirstName($data['firstName']);
        $user->setLastName($data['lastName']);
        $user->setEmail($data['email']);
        $user->setPhone($data['phone']);
        $user->setIsActive(true);
        $user->setActivationToken(null);
        $user->setBlockedAt(null);
        $user->setPassword($this->passwordHasher->hashPassword($user, self::TEST_PASSWORD));
    }

    /**
     * @param array{name: string, description: string, photoPath: ?string} $data
     */
    private function trainingType(ObjectManager $manager, array $data): TrainingType
    {
        $trainingType = $manager->getRepository(TrainingType::class)->findOneBy(['name' => $data['name']]) ?? new TrainingType();

        $trainingType->setName($data['name']);
        $trainingType->setDescription($data['description']);
        $trainingType->setPhotoPath($data['photoPath']);
        $manager->persist($trainingType);

        return $trainingType;
    }

    private function workTime(
        ObjectManager $manager,
        Trainer $trainer,
        string $date,
        string $startTime,
        string $endTime,
    ): TrainerWorkTime {
        $dateTime = new DateTimeImmutable($date);
        $workTime = $trainer->getId() === null
            ? new TrainerWorkTime()
            : $manager->getRepository(TrainerWorkTime::class)->findOneBy([
                'trainer' => $trainer,
                'date' => $dateTime,
            ]) ?? new TrainerWorkTime();

        $workTime->setTrainer($trainer);
        $workTime->setDate($dateTime);
        $workTime->setStartTime(new DateTimeImmutable($startTime));
        $workTime->setEndTime(new DateTimeImmutable($endTime));
        $manager->persist($workTime);

        return $workTime;
    }

    /**
     * @param array{name: string, price: int, durationDays: int, sessionLimit: ?int} $data
     */
    private function membershipPlan(ObjectManager $manager, array $data): MembershipPlan
    {
        $membershipPlan = $manager->getRepository(MembershipPlan::class)->findOneBy(['name' => $data['name']]) ?? new MembershipPlan();

        $membershipPlan->setName($data['name']);
        $membershipPlan->setPrice($data['price']);
        $membershipPlan->setDurationDays($data['durationDays']);
        $membershipPlan->setSessionLimit($data['sessionLimit']);
        $manager->persist($membershipPlan);

        return $membershipPlan;
    }
}
