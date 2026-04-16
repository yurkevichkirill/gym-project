<?php

declare(strict_types=1);

namespace App\ImportJob\DTO;

final readonly class ImportClients
{
    public function __construct(
        public ?string $email,
        public ?string $firstName,
        public ?string $lastName,
        public ?string $phone,
        public ?int    $age,
    ) {}

    public static function fromArray(CreateClientImport $data): self
    {
        return new self(
            email: $data?->email,
            firstName: $data?->firstName,
            lastName: $data?->lastName,
            phone: $data?->phone,
            age: isset($data->age) ? (int) $data->age : null,
        );
    }
}
