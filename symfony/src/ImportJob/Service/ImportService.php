<?php

declare(strict_types=1);

namespace App\ImportJob\Service;

use App\Client\Entity\Client;
use App\Client\Repository\ClientRepository;
use App\ImportJob\DTO\CreateClientImport;
use App\ImportJob\DTO\CreateClientImportBatch;
use App\ImportJob\Entity\ImportJob;
use Doctrine\ORM\EntityManagerInterface;
use Throwable;

final readonly class ImportService
{
    public function __construct(
        private EntityManagerInterface $em,
        private ClientRepository $clientRepo,
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
    public function import(CreateClientImport $dto): ?Client
    {
        $email = strtolower(trim($dto->email));

        $existingClient = $this->clientRepo->findOneBy([
            'email' => $email,
        ]);

        if ($existingClient) {
            return null;
        }

        $client = new Client();
        $client->setEmail($email);
        $client->setFirstName($dto->firstName);
        $client->setLastName($dto->lastName);
        $client->setPhone($dto->phone);
        $client->setAge($dto->age);

        $client->setIsActive(false);
        $client->setActivationToken(bin2hex(random_bytes(32)));

        $this->em->persist($client);

        return $client;

    }
}
