<?php

declare(strict_types=1);

namespace App\Command;

use App\Admin\Entity\Admin;
use App\Client\Entity\Client;
use App\MembershipPlan\Entity\MembershipPlan;
use App\Trainer\Entity\Trainer;
use App\TrainerWorkTime\DTO\CreateWorkTimeRequestDTO;
use App\TrainerWorkTime\Repository\TrainerWorkTimeRepository;
use App\TrainerWorkTime\Service\WorkTimeManager;
use App\TrainingType\Entity\TrainingType;
use App\User\Entity\User;
use App\User\Enum\UserRolesEnum;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:load-demo-data',
    description: 'Loads initial idempotent demo data for the production database.',
)]
final class LoadDemoDataCommand extends Command
{
    private const string PASSWORD = 'password';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly TrainerWorkTimeRepository $workTimeRepository,
        private readonly WorkTimeManager $workTimeManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->assertExpectedProductionDatabase();

        $trainingTypes = [];

        $this->entityManager->wrapInTransaction(function () use (&$trainingTypes): void {
            foreach ($this->trainingTypeData() as $data) {
                $trainingTypes[$data['name']] = $this->findOrCreateTrainingType(
                    $data['name'],
                    $data['description'],
                );
            }

            foreach ($this->membershipPlanData() as $data) {
                $this->findOrCreateMembershipPlan(
                    $data['name'],
                    $data['durationDays'],
                    $data['sessionLimit'],
                    $data['price'],
                );
            }

            $this->findOrCreateAdmin();

            foreach ($this->clientData() as $data) {
                $this->findOrCreateClient(
                    $data['email'],
                    $data['firstName'],
                    $data['lastName'],
                    $data['phone'],
                    $data['age'],
                );
            }

            foreach ($this->trainerData() as $data) {
                $this->findOrCreateTrainer(
                    $data['email'],
                    $data['firstName'],
                    $data['lastName'],
                    $data['phone'],
                    $data['pricePerHour'],
                    $trainingTypes[$data['trainingType']],
                );
            }

            $this->entityManager->flush();
            $this->setDemoAdminBalance();
        });

        foreach ($this->trainerData() as $data) {
            $trainer = $this->entityManager
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

        $this->verifyDemoPasswords();

        $output->writeln('Demo data loaded.');
        $output->writeln('Demo passwords verified.');

        return Command::SUCCESS;
    }

    private function verifyDemoPasswords(): void
    {
        foreach ($this->demoEmails() as $email) {
            $user = $this->entityManager
                ->getRepository(User::class)
                ->findOneBy(['email' => $email]);

            if (!$user instanceof User || !$this->passwordHasher->isPasswordValid($user, self::PASSWORD)) {
                throw new \RuntimeException(sprintf('Demo password check failed for %s.', $email));
            }
        }
    }

    /**
     * @return list<string>
     */
    private function demoEmails(): array
    {
        return [
            'admin@evogym.test',
            'client1@evogym.test',
            'client2@evogym.test',
            'client3@evogym.test',
            'client4@evogym.test',
            'client5@evogym.test',
            'trainer1@evogym.test',
            'trainer2@evogym.test',
            'trainer3@evogym.test',
        ];
    }

    private function assertExpectedProductionDatabase(): void
    {
        $databaseUrl = getenv('DATABASE_URL');

        if (!is_string($databaseUrl) || $databaseUrl === '') {
            throw new \RuntimeException('DATABASE_URL is not configured.');
        }

        $parts = parse_url($databaseUrl);
        $host = $parts['host'] ?? null;
        $database = isset($parts['path']) ? ltrim($parts['path'], '/') : null;

        if (getenv('APP_ENV') !== 'prod' || $host !== 'postgres' || $database !== 'gym_database') {
            throw new \RuntimeException('Refusing to load demo data into an unexpected database.');
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

    private function findOrCreateTrainingType(string $name, string $description): TrainingType
    {
        $trainingType = $this->entityManager
            ->getRepository(TrainingType::class)
            ->findOneBy(['name' => $name]);

        if ($trainingType instanceof TrainingType) {
            return $trainingType;
        }

        $trainingType = new TrainingType();
        $trainingType->setName($name);
        $trainingType->setDescription($description);

        $this->entityManager->persist($trainingType);

        return $trainingType;
    }

    private function findOrCreateMembershipPlan(
        string $name,
        int $durationDays,
        ?int $sessionLimit,
        int $price,
    ): MembershipPlan {
        $membershipPlan = $this->entityManager
            ->getRepository(MembershipPlan::class)
            ->findOneBy(['name' => $name]);

        if ($membershipPlan instanceof MembershipPlan) {
            return $membershipPlan;
        }

        $membershipPlan = new MembershipPlan();
        $membershipPlan->setName($name);
        $membershipPlan->setDurationDays($durationDays);
        $membershipPlan->setSessionLimit($sessionLimit);
        $membershipPlan->setPrice($price);

        $this->entityManager->persist($membershipPlan);

        return $membershipPlan;
    }

    private function findOrCreateAdmin(): Admin
    {
        $admin = $this->entityManager
            ->getRepository(Admin::class)
            ->findOneBy(['email' => 'admin@evogym.test']);

        if (!$admin instanceof Admin) {
            $admin = new Admin();
            $admin->setEmail('admin@evogym.test');
            $this->entityManager->persist($admin);
        }

        $admin->setFirstName('System');
        $admin->setLastName('Administrator');
        $admin->setPhone('+375290000000');
        $admin->setRoles([UserRolesEnum::ROLE_ADMIN->value]);
        $this->activate($admin);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, self::PASSWORD));

        return $admin;
    }

    private function setDemoAdminBalance(): void
    {
        $this->entityManager->getConnection()->update(
            '"user"',
            ['balance' => 0],
            ['email' => 'admin@evogym.test', 'type' => 'admin'],
        );
    }

    private function findOrCreateClient(
        string $email,
        string $firstName,
        string $lastName,
        string $phone,
        int $age,
    ): Client {
        $client = $this->entityManager
            ->getRepository(Client::class)
            ->findOneBy(['email' => $email]);

        if (!$client instanceof Client) {
            $client = new Client();
            $client->setEmail($email);
            $this->entityManager->persist($client);
        }

        $client->setFirstName($firstName);
        $client->setLastName($lastName);
        $client->setPhone($phone);
        $client->setAge($age);
        $client->setBalance(0);
        $this->activate($client);
        $client->setPassword($this->passwordHasher->hashPassword($client, self::PASSWORD));

        return $client;
    }

    private function findOrCreateTrainer(
        string $email,
        string $firstName,
        string $lastName,
        string $phone,
        int $pricePerHour,
        TrainingType $trainingType,
    ): Trainer {
        $trainer = $this->entityManager
            ->getRepository(Trainer::class)
            ->findOneBy(['email' => $email]);

        if (!$trainer instanceof Trainer) {
            $trainer = new Trainer();
            $trainer->setEmail($email);
            $this->entityManager->persist($trainer);
        }

        $trainer->setFirstName($firstName);
        $trainer->setLastName($lastName);
        $trainer->setPhone($phone);
        $trainer->setPricePerHour($pricePerHour);
        $trainer->setBalance(0);
        $trainer->setDebt(0);
        $trainer->setTrainingType($trainingType);
        $this->activate($trainer);
        $trainer->setPassword($this->passwordHasher->hashPassword($trainer, self::PASSWORD));

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
