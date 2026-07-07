<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Admin\Entity\Admin;
use App\Booking\Entity\Booking;
use App\Client\Entity\Client;
use App\Membership\Entity\Membership;
use App\MembershipPlan\Entity\MembershipPlan;
use App\Trainer\Entity\Trainer;
use App\TrainerWorkTime\DTO\CreateWorkTimeRequestDTO;
use App\TrainerWorkTime\Entity\TrainerWorkTime;
use App\TrainerWorkTime\Repository\TrainerWorkTimeRepository;
use App\TrainerWorkTime\Service\WorkTimeManager;
use App\Training\Entity\Training;
use App\TrainingType\Entity\TrainingType;
use App\User\Entity\User;
use App\User\Enum\UserRolesEnum;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AppFixtures extends Fixture
{
    private const string PASSWORD = 'password';

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly TrainerWorkTimeRepository $workTimeRepository,
        private readonly WorkTimeManager $workTimeManager,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
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

        $trainingTypes = [];

        foreach ($this->trainingTypeData() as $data) {
            $trainingTypes[$data['name']] = $this->createTrainingType(
                $manager,
                $data['name'],
                $data['description'],
            );
        }

        foreach ($this->membershipPlanData() as $data) {
            $this->createMembershipPlan(
                $manager,
                $data['name'],
                $data['durationDays'],
                $data['sessionLimit'],
                $data['price'],
            );
        }

        $this->createAdmin($manager);

        foreach ($this->clientData() as $data) {
            $this->createClient(
                $manager,
                $data['email'],
                $data['firstName'],
                $data['lastName'],
                $data['phone'],
                $data['age'],
            );
        }

        foreach ($this->trainerData() as $data) {
            $this->createTrainer(
                $manager,
                $data['email'],
                $data['firstName'],
                $data['lastName'],
                $data['phone'],
                $data['pricePerHour'],
                $trainingTypes[$data['trainingType']],
            );
        }

        $manager->flush();
        $this->setDemoAdminBalance($manager);

        foreach ($this->trainerData() as $data) {
            $trainer = $manager
                ->getRepository(Trainer::class)
                ->findOneBy(['email' => $data['email']]);

            if (!$trainer instanceof Trainer) {
                continue;
            }

            foreach (['2026-08-01', '2026-08-03', '2026-08-05'] as $date) {
                $workDate = new DateTimeImmutable($date);

                if ($this->workTimeRepository->findByDateForTrainer($trainer, $workDate) !== null) {
                    continue;
                }

                $this->workTimeManager->create(
                    $trainer,
                    new CreateWorkTimeRequestDTO(
                        startTime: '09:00',
                        endTime: '18:00',
                        date: $date,
                    ),
                );
            }
        }
    }

    /**
     * @return list<array{name: string, description: string}>
     */
    private function trainingTypeData(): array
    {
        return [
            [
                'name' => 'Strength Training',
                'description' => 'Progressive resistance sessions for full-body strength.',
            ],
            [
                'name' => 'Functional Training',
                'description' => 'Practical movement patterns for mobility and conditioning.',
            ],
            [
                'name' => 'Yoga',
                'description' => 'Guided flexibility, balance, and breathing practice.',
            ],
            [
                'name' => 'Boxing',
                'description' => 'Technique, footwork, and conditioning with boxing drills.',
            ],
        ];
    }

    /**
     * @return list<array{name: string, durationDays: int, sessionLimit: ?int, price: int}>
     */
    private function membershipPlanData(): array
    {
        return [
            ['name' => 'Basic', 'durationDays' => 30, 'sessionLimit' => 4, 'price' => 1000],
            ['name' => 'Standard', 'durationDays' => 30, 'sessionLimit' => 12, 'price' => 2500],
            ['name' => 'Unlimited', 'durationDays' => 30, 'sessionLimit' => null, 'price' => 5000],
        ];
    }

    /**
     * @return list<array{email: string, firstName: string, lastName: string, phone: string, age: int}>
     */
    private function clientData(): array
    {
        return [
            ['email' => 'client1@evogym.test', 'firstName' => 'John', 'lastName' => 'Smith', 'phone' => '+375290000001', 'age' => 28],
            ['email' => 'client2@evogym.test', 'firstName' => 'Emily', 'lastName' => 'Johnson', 'phone' => '+375290000002', 'age' => 31],
            ['email' => 'client3@evogym.test', 'firstName' => 'Michael', 'lastName' => 'Brown', 'phone' => '+375290000003', 'age' => 35],
            ['email' => 'client4@evogym.test', 'firstName' => 'Sophia', 'lastName' => 'Davis', 'phone' => '+375290000004', 'age' => 26],
            ['email' => 'client5@evogym.test', 'firstName' => 'Daniel', 'lastName' => 'Wilson', 'phone' => '+375290000005', 'age' => 33],
        ];
    }

    /**
     * @return list<array{email: string, firstName: string, lastName: string, phone: string, pricePerHour: int, trainingType: string}>
     */
    private function trainerData(): array
    {
        return [
            ['email' => 'trainer1@evogym.test', 'firstName' => 'Alex', 'lastName' => 'Carter', 'phone' => '+375290000101', 'pricePerHour' => 1500, 'trainingType' => 'Strength Training'],
            ['email' => 'trainer2@evogym.test', 'firstName' => 'Maria', 'lastName' => 'Lopez', 'phone' => '+375290000102', 'pricePerHour' => 2000, 'trainingType' => 'Yoga'],
            ['email' => 'trainer3@evogym.test', 'firstName' => 'David', 'lastName' => 'Miller', 'phone' => '+375290000103', 'pricePerHour' => 2500, 'trainingType' => 'Boxing'],
        ];
    }

    private function createTrainingType(ObjectManager $manager, string $name, string $description): TrainingType
    {
        $trainingType = new TrainingType();
        $trainingType->setName($name);
        $trainingType->setDescription($description);

        $manager->persist($trainingType);

        return $trainingType;
    }

    private function createMembershipPlan(
        ObjectManager $manager,
        string $name,
        int $durationDays,
        ?int $sessionLimit,
        int $price,
    ): MembershipPlan {
        $membershipPlan = new MembershipPlan();
        $membershipPlan->setName($name);
        $membershipPlan->setDurationDays($durationDays);
        $membershipPlan->setSessionLimit($sessionLimit);
        $membershipPlan->setPrice($price);

        $manager->persist($membershipPlan);

        return $membershipPlan;
    }

    private function createAdmin(ObjectManager $manager): Admin
    {
        $admin = new Admin();
        $admin->setEmail('admin@evogym.test');
        $admin->setFirstName('System');
        $admin->setLastName('Administrator');
        $admin->setPhone('+375290000000');
        $admin->setRoles([UserRolesEnum::ROLE_ADMIN->value]);
        $this->activate($admin);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, self::PASSWORD));

        $manager->persist($admin);

        return $admin;
    }

    private function setDemoAdminBalance(ObjectManager $manager): void
    {
        if (!$manager instanceof EntityManagerInterface) {
            return;
        }

        $manager->getConnection()->update(
            '"user"',
            ['balance' => 0],
            ['email' => 'admin@evogym.test', 'type' => 'admin'],
        );
    }

    private function createClient(
        ObjectManager $manager,
        string $email,
        string $firstName,
        string $lastName,
        string $phone,
        int $age,
    ): Client {
        $client = new Client();
        $client->setEmail($email);
        $client->setFirstName($firstName);
        $client->setLastName($lastName);
        $client->setPhone($phone);
        $client->setAge($age);
        $client->setBalance(0);
        $this->activate($client);
        $client->setPassword($this->passwordHasher->hashPassword($client, self::PASSWORD));

        $manager->persist($client);

        return $client;
    }

    private function createTrainer(
        ObjectManager $manager,
        string $email,
        string $firstName,
        string $lastName,
        string $phone,
        int $pricePerHour,
        TrainingType $trainingType,
    ): Trainer {
        $trainer = new Trainer();
        $trainer->setEmail($email);
        $trainer->setFirstName($firstName);
        $trainer->setLastName($lastName);
        $trainer->setPhone($phone);
        $trainer->setPricePerHour($pricePerHour);
        $trainer->setBalance(0);
        $trainer->setDebt(0);
        $trainer->setTrainingType($trainingType);
        $this->activate($trainer);
        $trainer->setPassword($this->passwordHasher->hashPassword($trainer, self::PASSWORD));

        $manager->persist($trainer);

        return $trainer;
    }

    private function activate(User $user): void
    {
        $user->setIsActive(true);
        $user->setActivationToken(null);
        $user->setBlockedAt(null);
        $user->setDeletedAt(null);
    }
}
