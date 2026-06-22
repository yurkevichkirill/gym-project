<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Doctrine;

use App\Client\DTO\ClientResponseDTO;
use App\Client\DTO\GetClientsRequestDTO;
use App\Client\Entity\Client;
use App\Client\Query\ClientQuery;
use App\Client\Service\ClientManager;
use App\Trainer\DTO\ResolvedTrainersRequestAdminDTO;
use App\Trainer\DTO\TrainerResponsePrivateDTO;
use App\Trainer\Entity\Trainer;
use App\Trainer\Query\TrainersQueryAdmin;
use App\Trainer\Service\TrainerManager;
use App\TrainingType\Entity\TrainingType;
use App\User\Exception\UserNotDeletedException;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

final class SoftDeleteWorkflowTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private TagAwareCacheInterface $cache;
    private ClientQuery $clientQuery;
    private TrainersQueryAdmin $trainersQuery;
    private ClientManager $clientManager;
    private TrainerManager $trainerManager;

    protected function setUp(): void
    {
        self::bootKernel();

        $container = self::getContainer();

        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->cache = $container->get(TagAwareCacheInterface::class);
        $this->clientQuery = $container->get(ClientQuery::class);
        $this->trainersQuery = $container->get(TrainersQueryAdmin::class);
        $this->clientManager = $container->get(ClientManager::class);
        $this->trainerManager = $container->get(TrainerManager::class);

        $this->entityManager->getConnection()->beginTransaction();

        $this->cache->invalidateTags([
            'clients_list',
            'trainers_list',
        ]);
    }

    protected function tearDown(): void
    {
        if (
            isset($this->entityManager)
            && $this->entityManager->getConnection()->isTransactionActive()
        ) {
            $this->entityManager->getConnection()->rollBack();
        }

        if (isset($this->entityManager)) {
            $this->entityManager->close();
        }

        parent::tearDown();
    }

    public function testClientIsDeletedFilterReturnsDeletedClientsAndRestoresFilter(): void
    {
        $balance = 1_900_000_001;

        $activeClient = $this->persistClient(
            balance: $balance,
            deleted: false,
        );

        $deletedClient = $this->persistClient(
            balance: $balance,
            deleted: true,
        );

        $activeClientId = $activeClient->getId();
        $deletedClientId = $deletedClient->getId();

        self::assertIsInt($activeClientId);
        self::assertIsInt($deletedClientId);

        $deletedDto = new GetClientsRequestDTO(
            minBalance: $balance,
            maxBalance: $balance,
            isDeleted: true,
            sort: 'age:ASC',
            page: 1,
            limit: 100,
        );

        $deletedResult = $this->clientQuery->getCachedData(
            $deletedDto,
            $this->clientQuery->getParsedSort($deletedDto),
        );

        self::assertSame(
            [$deletedClientId],
            $this->extractClientIds($deletedResult['items']),
        );

        self::assertTrue(
            $this->entityManager->getFilters()->isEnabled('softdeleteable'),
        );

        $activeDto = new GetClientsRequestDTO(
            minBalance: $balance,
            maxBalance: $balance,
            isDeleted: false,
            sort: 'age:ASC',
            page: 1,
            limit: 100,
        );

        $activeResult = $this->clientQuery->getCachedData(
            $activeDto,
            $this->clientQuery->getParsedSort($activeDto),
        );

        self::assertSame(
            [$activeClientId],
            $this->extractClientIds($activeResult['items']),
        );
    }

    public function testTrainerIsDeletedFilterReturnsDeletedTrainersAndRestoresFilter(): void
    {
        $trainingType = $this->persistTrainingType();

        $activeTrainer = $this->persistTrainer(
            trainingType: $trainingType,
            deleted: false,
        );

        $deletedTrainer = $this->persistTrainer(
            trainingType: $trainingType,
            deleted: true,
        );

        $activeTrainerId = $activeTrainer->getId();
        $deletedTrainerId = $deletedTrainer->getId();

        self::assertIsInt($activeTrainerId);
        self::assertIsInt($deletedTrainerId);

        $deletedDto = new ResolvedTrainersRequestAdminDTO(
            trainingType: $trainingType,
            isDeleted: true,
            sort: 'lastName:ASC',
            page: 1,
            limit: 100,
        );

        $deletedResult = $this->trainersQuery->getCachedData(
            $deletedDto,
            $this->trainersQuery->getParsedSort($deletedDto),
        );

        self::assertSame(
            [$deletedTrainerId],
            $this->extractTrainerIds($deletedResult['items']),
        );

        self::assertTrue(
            $this->entityManager->getFilters()->isEnabled('softdeleteable'),
        );

        $activeDto = new ResolvedTrainersRequestAdminDTO(
            trainingType: $trainingType,
            isDeleted: false,
            sort: 'lastName:ASC',
            page: 1,
            limit: 100,
        );

        $activeResult = $this->trainersQuery->getCachedData(
            $activeDto,
            $this->trainersQuery->getParsedSort($activeDto),
        );

        self::assertSame(
            [$activeTrainerId],
            $this->extractTrainerIds($activeResult['items']),
        );
    }

    public function testDeletedClientCanBeRestoredById(): void
    {
        $client = $this->persistClient(
            balance: 0,
            deleted: true,
        );

        $clientId = $client->getId();
        self::assertIsInt($clientId);

        $this->entityManager->clear();

        $restoredClient = $this->clientManager->restore($clientId);

        self::assertSame($clientId, $restoredClient->getId());
        self::assertNull($restoredClient->getDeletedAt());
        self::assertTrue(
            $this->entityManager->getFilters()->isEnabled('softdeleteable'),
        );

        $this->entityManager->clear();

        self::assertInstanceOf(
            Client::class,
            $this->entityManager->find(Client::class, $clientId),
        );
    }

    public function testDeletedTrainerCanBeRestoredById(): void
    {
        $trainingType = $this->persistTrainingType();

        $trainer = $this->persistTrainer(
            trainingType: $trainingType,
            deleted: true,
        );

        $trainerId = $trainer->getId();
        self::assertIsInt($trainerId);

        $this->entityManager->clear();

        $restoredTrainer = $this->trainerManager->restore($trainerId);

        self::assertSame($trainerId, $restoredTrainer->getId());
        self::assertNull($restoredTrainer->getDeletedAt());
        self::assertTrue(
            $this->entityManager->getFilters()->isEnabled('softdeleteable'),
        );

        $this->entityManager->clear();

        self::assertInstanceOf(
            Trainer::class,
            $this->entityManager->find(Trainer::class, $trainerId),
        );
    }

    public function testRestoringActiveClientThrowsConflict(): void
    {
        $client = $this->persistClient(
            balance: 0,
            deleted: false,
        );

        $clientId = $client->getId();
        self::assertIsInt($clientId);

        $this->entityManager->clear();

        $this->expectException(UserNotDeletedException::class);
        $this->expectExceptionMessage('Client is not deleted');

        $this->clientManager->restore($clientId);
    }

    public function testRestoringActiveTrainerThrowsConflict(): void
    {
        $trainingType = $this->persistTrainingType();

        $trainer = $this->persistTrainer(
            trainingType: $trainingType,
            deleted: false,
        );

        $trainerId = $trainer->getId();
        self::assertIsInt($trainerId);

        $this->entityManager->clear();

        $this->expectException(UserNotDeletedException::class);
        $this->expectExceptionMessage('Trainer is not deleted');

        $this->trainerManager->restore($trainerId);
    }

    private function persistClient(int $balance, bool $deleted): Client
    {
        $suffix = bin2hex(random_bytes(8));

        $client = new Client();
        $client->setFirstName('Soft');
        $client->setLastName('Delete');
        $client->setEmail("soft_delete_client_{$suffix}@example.com");
        $client->setPhone('+37529' . random_int(1_000_000, 9_999_999));
        $client->setPassword('password');
        $client->setAge(30);
        $client->setBalance($balance);
        $client->setIsActive(true);

        if ($deleted) {
            $client->setDeletedAt(new DateTime());
        }

        $this->entityManager->persist($client);
        $this->entityManager->flush();

        return $client;
    }

    private function persistTrainer(
        TrainingType $trainingType,
        bool $deleted,
    ): Trainer {
        $suffix = bin2hex(random_bytes(8));

        $trainer = new Trainer();
        $trainer->setFirstName('Soft');
        $trainer->setLastName('Delete');
        $trainer->setEmail("soft_delete_trainer_{$suffix}@example.com");
        $trainer->setPhone('+37533' . random_int(1_000_000, 9_999_999));
        $trainer->setPassword('password');
        $trainer->setPricePerHour(1_000);
        $trainer->setTrainingType($trainingType);
        $trainer->setIsActive(true);

        if ($deleted) {
            $trainer->setDeletedAt(new DateTime());
        }

        $this->entityManager->persist($trainer);
        $this->entityManager->flush();

        return $trainer;
    }

    private function persistTrainingType(): TrainingType
    {
        $suffix = bin2hex(random_bytes(8));

        $trainingType = new TrainingType();
        $trainingType->setName("Soft delete {$suffix}");
        $trainingType->setDescription('Soft-delete integration test');

        $this->entityManager->persist($trainingType);
        $this->entityManager->flush();

        return $trainingType;
    }

    /**
     * @param list<mixed> $items
     *
     * @return list<int>
     */
    private function extractClientIds(array $items): array
    {
        $ids = [];

        foreach ($items as $item) {
            self::assertInstanceOf(ClientResponseDTO::class, $item);
            $ids[] = $item->id;
        }

        sort($ids);

        return $ids;
    }

    /**
     * @param list<mixed> $items
     *
     * @return list<int>
     */
    private function extractTrainerIds(array $items): array
    {
        $ids = [];

        foreach ($items as $item) {
            self::assertInstanceOf(TrainerResponsePrivateDTO::class, $item);
            $ids[] = $item->id;
        }

        sort($ids);

        return $ids;
    }
}
