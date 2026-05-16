<?php

declare(strict_types=1);

namespace App\Controller\Public;

use App\Response\ResponseTypeDTO\CollectionResponse;
use App\Response\ResponseTypeDTO\ItemResponse;
use App\Response\SwaggerDocDTO\AbstractCollectionResponseDTO;
use App\Response\SwaggerDocDTO\AbstractItemResponseDTO;
use App\Response\SwaggerDocDTO\ErrorResponseDTO;
use App\Trainer\DTO\ResolvedTrainersRequestDTO;
use App\Trainer\DTO\TrainerResponseDTO;
use App\Trainer\Entity\Trainer;
use App\Trainer\Mapper\TrainerMapperInterface;
use App\Trainer\Query\TrainersQuery;
use App\Trainer\Service\TrainerManager;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class TrainerController extends AbstractController
{
    /**
     * @throws InvalidArgumentException
     * @throws BadRequestHttpException
     */
    #[Route('/api/trainers/', methods: ['GET'], format: 'json')]
    #[Cache(maxage: 0, smaxage: 3600, public: true, mustRevalidate: true)]
    #[OA\Get(
        operationId: 'getTrainers',
        summary: 'Get list of trainers with filters.',
        tags: ['Public: Trainers'],
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
                description: 'Collection of trainers',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: new Model(type: AbstractCollectionResponseDTO::class)),
                        new OA\Schema(
                            properties: [
                                new OA\Property(
                                    property: 'data',
                                    type: 'array',
                                    items: new OA\Items(ref: new Model(type: TrainerResponseDTO::class))
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Invalid query parameters',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 404,
                description: 'Training type not found',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            )
        ]
    )]
    public function getAll(
        ResolvedTrainersRequestDTO $resolvedDto,
        TrainersQuery $handler,
    ): CollectionResponse {
        $parsedSort = $handler->getParsedSort($resolvedDto);

        $cachedData = $handler->getCachedData($resolvedDto, $parsedSort);

        return new CollectionResponse(
            $cachedData['items'],
            $resolvedDto->page,
            $resolvedDto->limit,
            $cachedData['total'],
            $parsedSort,
            Response::HTTP_OK,
        );
    }

    /**
     * @throws NotFoundHttpException
     */
    #[Route('/api/trainers/{id}/', methods: ['GET'], format: 'json')]
    #[OA\Get(
        operationId: 'getTrainer',
        summary: 'Get public trainer profile.',
        tags: ['Public: Trainers'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Trainer details',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: new Model(type: AbstractItemResponseDTO::class)),
                        new OA\Schema(
                            properties: [
                                new OA\Property(
                                    property: 'data',
                                    ref: new Model(type: TrainerResponseDTO::class)
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Trainer not found',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            )
        ]
    )]
    public function get(
        Trainer $trainer,
        TrainerMapperInterface $mapper,
        TrainerManager $manager,
    ): ItemResponse
    {
        return new ItemResponse(
            data: $mapper->map($manager->getAvailable($trainer)),
            status: Response::HTTP_OK,
        );
    }
}
