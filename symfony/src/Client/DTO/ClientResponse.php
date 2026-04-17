<?php

declare(strict_types=1);

namespace App\Client\DTO;

use App\Client\Entity\Client;

readonly class ClientResponse
{
    public function __construct(
        public int $id,
        public int $age,
        public string $firstName,
        public string $lastName,
        public string $email,
        public string $phone,
        public string $createdAt,
        public string $deletedAt,
        public string $updatedAt,
        public int $balance,
        public string $type,
        public string $blockedAt,
    )
    {}

    public static function fromEntity(Client $client): self
    {
        return new self(
            id: $client->getId(),
            age: $client->getAge(),
            firstName: $client->getFirstName(),
            lastName: $client->getLastName(),
            email: $client->getEmail(),
            phone: $client->getPhone(),
            createdAt: $client->getCreatedAt()?->format(DATE_ATOM) ?? '',
            deletedAt: $client->getDeletedAt()?->format(DATE_ATOM) ?? '',
            updatedAt: $client->getUpdatedAt()?->format(DATE_ATOM) ?? '',
            balance: $client->getBalance(),
            type: 'client',
            blockedAt: $client->getBlockedAt()?->format(DATE_ATOM) ?? '',
        );
    }
}
