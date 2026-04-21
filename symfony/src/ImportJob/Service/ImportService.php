<?php

declare(strict_types=1);

namespace App\ImportJob\Service;

use App\Client\Entity\Client;
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
        private LoggerInterface $clientLogger,
    ) {}

    public function create(CreateClientImportBatch $dto): ImportJob
    {
        $context = [
            'domain' => 'client',
            'operation' => 'create_job',
            'total' => count($dto->clients),
        ];

        $this->clientLogger->info('Import job creation started', $this->ctx($context, 'started'));
        $job = new ImportJob(count($dto->clients));

        $this->em->persist($job);
        $this->em->flush();

        $this->clientLogger->info('Import job created', $this->ctx($context + [
                'job_id' => $job->getId(),
            ], 'succeeded'));

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

        $this->clientLogger->info('Client import started', $this->ctx($context, 'started'));

        $email = strtolower(trim($dto->email));

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

        } catch (UniqueConstraintViolationException) {
            $this->clientLogger->notice('Client import skipped (duplicate)', $this->ctx($context, 'skipped'));

            return ImportResultEnum::SKIPPED;
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
