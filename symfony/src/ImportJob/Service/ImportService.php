<?php

declare(strict_types=1);

namespace App\ImportJob\Service;

use App\Client\Entity\Client;
use App\Client\Repository\ClientRepository;
use App\ImportJob\DTO\CreateClientImport;
use App\ImportJob\DTO\CreateClientImportBatch;
use App\ImportJob\Entity\ImportJob;
use App\ImportJob\Enum\ImportResultEnum;
use App\ImportJob\Repository\ImportJobRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ImportService
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function create(CreateClientImportBatch $dto): ImportJob
    {
        $job = new ImportJob(count($dto->clients));

        $this->em->persist($job);
        $this->em->flush();

        return $job;
    }

    public function import(CreateClientImport $dto): ImportResultEnum
    {
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

            return ImportResultEnum::CREATED;

        } catch (UniqueConstraintViolationException) {
            return ImportResultEnum::SKIPPED;
        }
    }
}
