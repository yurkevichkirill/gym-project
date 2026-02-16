<?php

declare(strict_types=1);

namespace App\Client\Service;

use App\Client\DTO\CreateClientRequest;
use App\Client\Entity\Client;
use App\Client\Repository\ClientRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class ClientManager
{
    public function __construct(
        private ClientRepository $clientRepo,
    )
    {}

    public function create(CreateClientRequest $dto, UserPasswordHasherInterface $passwordHasher): Client
    {
        $client = new Client();
        $client->setFirstName($dto->firstName);
        $client->setLastName($dto->lastName);
        $client->setEmail($dto->email);
        $client->setPhone($dto->phone);
        $client->setAge($dto->age);

        $plaintextPassword = $dto->password;
        $hashedPassword = $passwordHasher->hashPassword(
            $client,
            $plaintextPassword
        );
        $client->setPassword($hashedPassword);

        $this->clientRepo->create($client);

        return $client;
    }
}
