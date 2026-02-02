<?php

declare(strict_types=1);

namespace App\Client\Service;

use App\Client\Repository\ClientRepository;
use App\Client\Service\ClientServiceInterface;

readonly class ClientService implements ClientServiceInterface
{
    public function __construct(
        private ClientRepository $clientRepo
    )
    {}

    public function findBy(array $sort): array
    {
        return $this->clientRepo->findBy([], orderBy: $sort);
    }
}
