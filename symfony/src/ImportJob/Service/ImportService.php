<?php

declare(strict_types=1);

namespace App\ImportJob\Service;

use App\Client\Entity\Client;
use App\Client\Repository\ClientRepository;
use App\ImportJob\DTO\CreateClientImport;
use App\ImportJob\DTO\CreateClientImportBatch;
use App\ImportJob\Entity\ImportJob;
use App\ImportJob\Enum\ImportResultEnum;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

final readonly class ImportService
{
    public function __construct(
        private EntityManagerInterface $em,
        private ClientRepository $clientRepo,
        private LoggerInterface $clientLogger,
    ) {}

    public function create(CreateClientImportBatch $dto): ImportJob
    {
        $job = new ImportJob(count($dto->clients));

        $this->em->persist($job);
        $this->em->flush();

        return $job;
    }

    /**
     * @throws Throwable
     */
    public function import(CreateClientImport $dto): ImportResultEnum
    {
        $context = [
            'domain' => 'client',
            'operation' => 'import_client',
            'email' => $dto->email,
        ];

        $email = strtolower(trim($dto->email));

        $existingClient = $this->clientRepo->findOneBy([
            'email' => $email,
        ]);

        if ($existingClient) {
            return ImportResultEnum::SKIPPED;
        }

        $client = new Client();
        $client->setEmail($email);
        $client->setFirstName($dto->firstName);
        $client->setLastName($dto->lastName);
        $client->setPhone($dto->phone);
        $client->setAge($dto->age);

        $client->setPassword(password_hash('temp1234', PASSWORD_BCRYPT));

        try {
            $this->em->persist($client);
            $this->em->flush();

            $this->clientLogger->info('Client imported', $this->ctx($context + [
                    'client_id' => $client->getId(),
                ], 'created'));

            return ImportResultEnum::CREATED;

        } catch (Throwable $e) {
            $this->clientLogger->error('Client import failed', $this->ctx($context, 'failed', [
                'exception_class' => $e::class,
                'error' => $e->getMessage(),
                'exception' => $e,
            ]));

            throw $e;
        }
    }

    private function ctx(array $context, string $outcome, array $extra = []): array
    {
        return $extra + $context + [
                'domain' => 'client',
                'outcome' => $outcome,
            ];
    }
}
