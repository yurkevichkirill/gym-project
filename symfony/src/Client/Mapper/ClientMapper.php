<?php

declare(strict_types=1);

namespace App\Client\Mapper;

use App\Client\DTO\ClientResponse;
use App\Client\Entity\Client;

class ClientMapper implements ClientMapperInterface
{
    public function map(Client $client): ClientResponse
    {
        return ClientResponse::fromEntity($client);
    }
}
