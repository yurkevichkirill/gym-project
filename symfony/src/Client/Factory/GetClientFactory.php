<?php

declare(strict_types=1);

namespace App\Client\Factory;

use App\Client\DTO\ClientFilter;
use App\Client\DTO\GetClients;
use App\Request\Utils\RequestParser;
use Symfony\Component\HttpFoundation\Request;

final readonly class GetClientFactory
{
    public function __construct(
        private RequestParser $parser,
    )
    {}

    public function fromRequest(Request $request): GetClients
    {
        $filter = new ClientFilter(
            minAge: $this->parser->toInt($request->query->get('minAge')),
            maxAge: $this->parser->toInt($request->query->get('maxAge')),
            minBalance: $this->parser->toInt($request->query->get('minBalance')),
            maxBalance: $this->parser->toInt($request->query->get('maxBalance')),
            isDeleted: $this->parser->toBool($request->query->get('isDeleted')),
        );

        $allowedSortParams = ['firstName', 'lastName', 'balance', 'age', 'createdAt', 'updatedAt', 'deletedAt'];

        return new GetClients(
            sort: $this->parser->parseSort($request->query->get('sort', 'age:ASC'), $allowedSortParams),
            filter: $filter,
            page: $this->parser->toPositiveInt($request->query->get('page'), 'page') ?? 1,
            limit: $this->parser->toPositiveInt($request->query->get('limit'), 'limit') ?? 20,
        );
    }
}
