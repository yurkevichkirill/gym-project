<?php

declare(strict_types=1);

namespace App\TrainingType\Factory;

use App\Request\Utils\RequestParser;
use App\TrainingType\DTO\GetTrainingTypes;
use Symfony\Component\HttpFoundation\Request;

final readonly class GetTrainingTypesFactory
{
    public function __construct(
        private RequestParser $parser
    ) {}

    public function fromRequest(Request $request): GetTrainingTypes
    {
        $allowedSortParams = ['name'];

        return new GetTrainingTypes(
            sort: $this->parser->parseSort($request->query->get('sort', 'name:ASC'), $allowedSortParams),
            page: $this->parser->toPositiveInt($request->query->get('page'), 'page'),
            limit: $this->parser->toPositiveInt($request->query->get('limit'), 'limit'),
        );
    }
}
