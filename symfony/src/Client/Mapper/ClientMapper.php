<?php

declare(strict_types=1);

namespace App\Client\Mapper;

use App\Client\DTO\ClientResponseDTO;
use App\Client\Entity\Client;

class ClientMapper implements ClientMapperInterface
{
    public function map(Client $client): ClientResponseDTO
    {
        return ClientResponseDTO::fromEntity($client);
    }
}
