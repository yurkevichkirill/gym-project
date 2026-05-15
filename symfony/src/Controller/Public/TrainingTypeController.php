<?php

namespace App\Controller\Public;

use App\Response\CollectionResponse;
use App\Response\ItemResponse;
use App\TrainingType\DTO\TrainingTypeResponseDto;
use App\TrainingType\Entity\TrainingType;
use App\TrainingType\Factory\GetTrainingTypesFactory;
use App\TrainingType\Mapper\TrainingTypeMapperInterface;
use App\TrainingType\Query\TrainingTypeQuery;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\Routing\Attribute\Route;

final class TrainingTypeController extends AbstractController
{
    /**
     * @throws InvalidArgumentException
     */
    #[Route('/api/training/types/', methods: ['GET'], format: 'json')]
    #[Cache(maxage: 0, smaxage: 3600, public: true, mustRevalidate: true)]
    #[OA\Get(
        operationId: 'getTrainingTypes',
        summary: 'Get all available training types.',
        tags: ['All: TrainingType'],
        parameters: [
            new OA\Parameter(name: 'sort', in: 'query', schema: new OA\Schema(type: 'string'), example: 'name:ASC'),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'items',
                            type: 'array',
                            items: new OA\Items(ref: new Model(type: TrainingTypeResponseDto::class))
                        ),
                        new OA\Property(property: 'total', type: 'integer'),
                        new OA\Property(property: 'page', type: 'integer'),
                        new OA\Property(property: 'limit', type: 'integer'),
                    ]
                )
            )
        ]
    )]
    public function getAll(
        TrainingTypeQuery $handler,
        GetTrainingTypesFactory $factory,
        TrainingTypeMapperInterface $mapper,
        Request $request
    ): CollectionResponse
    {
        $queryDto = $factory->fromRequest($request);

        $trainingTypes = $handler->handle($queryDto);

        return new CollectionResponse(
            array_map(fn ($type) => $mapper->map($type), $trainingTypes),
            $queryDto->page,
            $queryDto->limit,
            $handler->getTotal(),
            $queryDto->sort,
            Response::HTTP_OK,
        );
    }

    #[Route('/api/training/types/{id}/', methods: ['GET'], format: 'json')]
    #[OA\Get(
        operationId: 'getTrainingType',
        summary: 'Get a specific training type by ID.',
        tags: ['All: TrainingType'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: new Model(type: TrainingTypeResponseDto::class))
            ),
            new OA\Response(response: 404, description: 'Training type not found')
        ]
    )]
    public function get(
        TrainingType $trainingType,
        TrainingTypeMapperInterface $mapper,
    ): ItemResponse
    {
        return new ItemResponse(
            data: $mapper->map($trainingType),
            status: Response::HTTP_OK,
        );
    }
}
