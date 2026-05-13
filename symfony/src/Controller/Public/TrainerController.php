<?php

namespace App\Controller\Public;

use App\Response\CollectionResponse;
use App\Response\ItemResponse;
use App\Trainer\DTO\TrainerResponse;
use App\Trainer\Entity\Trainer;
use App\Trainer\Factory\GetTrainersFactory;
use App\Trainer\Mapper\TrainerMapperInterface;
use App\Trainer\Query\TrainersQuery;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\Routing\Attribute\Route;

final class TrainerController extends AbstractController
{
    /**
     * @throws InvalidArgumentException
     */
    #[Route('/api/trainers/', methods: ['GET'], format: 'json')]
    #[Cache(maxage: 3600, public: true, mustRevalidate: true)]
    #[OA\Get(
        operationId: 'getTrainers',
        summary: 'Get list of trainers with filters.',
        tags: ['All: Trainers'],
        parameters: [
            new OA\Parameter(name: 'minPricePerHour', in: 'query', schema: new OA\Schema(type: 'integer'), example: 30),
            new OA\Parameter(name: 'maxPricePerHour', in: 'query', schema: new OA\Schema(type: 'integer'), example: 50),
            new OA\Parameter(name: 'trainingTypeId', in: 'query', schema: new OA\Schema(type: 'integer'), example: 1),
            new OA\Parameter(name: 'sort', in: 'query', schema: new OA\Schema(type: 'string'), example: 'lastName:ASC'),
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
                            items: new OA\Items(ref: new Model(type: TrainerResponse::class))
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
        Request $request,
        TrainerMapperInterface $mapper,
        TrainersQuery $handler,
        GetTrainersFactory $factory,
    ): CollectionResponse {
        $queryDto = $factory->fromRequest($request);
        $trainers = $handler->handle($queryDto);

        return new CollectionResponse(
            array_map(fn ($trainer) => $mapper->map($trainer), $trainers),
            $queryDto->page,
            $queryDto->limit,
            $handler->getTotal($queryDto->filter),
            $queryDto->sort,
            Response::HTTP_OK,
        );
    }

    #[Route('/api/trainers/{id}/', methods: ['GET'], format: 'json')]
    #[OA\Get(
        operationId: 'getTrainer',
        summary: 'Get public trainer profile.',
        tags: ['All: Trainers'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: new Model(type: TrainerResponse::class))
            ),
            new OA\Response(response: 404, description: 'Trainer not found')
        ]
    )]
    public function get(Trainer $trainer, TrainerMapperInterface $mapper): ItemResponse
    {
        return new ItemResponse(
            data: $mapper->map($trainer),
            status: Response::HTTP_OK,
        );
    }
}
