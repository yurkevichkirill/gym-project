<?php

declare(strict_types=1);

namespace App\Controller\Public;

use App\Response\CollectionResponse;
use App\Response\DTO\AbstractCollectionResponseDTO;
use App\Response\DTO\AbstractItemResponseDTO;
use App\Response\DTO\ErrorResponseDTO;
use App\Response\ItemResponse;
use App\TrainerWorkTime\DTO\ResolvedWorktimesRequestDTO;
use App\TrainerWorkTime\DTO\WorkTimeResponse;
use App\TrainerWorkTime\Entity\TrainerWorkTime;
use App\TrainerWorkTime\Mapper\WorkTimeMapperInterface;
use App\TrainerWorkTime\Query\WorkTimeQuery;
use App\TrainerWorkTime\Service\WorkTimeManager;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class TrainerWorkTimeController extends AbstractController
{
    /**
     * @throws InvalidArgumentException
     * @throws BadRequestHttpException
     */
    #[Route('/api/worktime/', methods: ['GET'], format: 'json')]
    #[Cache(maxage: 0, smaxage: 3600, public: true, mustRevalidate: true)]
    #[OA\Get(
        operationId: 'getWorkTimes',
        summary: 'Get all available work time slots.',
        tags: ['Public: WorkTime'],
        parameters: [
            new OA\Parameter(name: 'trainerId', in: 'query', schema: new OA\Schema(type: 'integer'), example: 1),
            new OA\Parameter(name: 'date', in: 'query', schema: new OA\Schema(type: 'string', format: 'date'), example: '10-03-2026'),
            new OA\Parameter(name: 'sort', in: 'query', schema: new OA\Schema(type: 'string'), example: 'date:ASC'),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of work time slots',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: new Model(type: AbstractCollectionResponseDTO::class)),
                        new OA\Schema(
                            properties: [
                                new OA\Property(
                                    property: 'data',
                                    type: 'array',
                                    items: new OA\Items(ref: new Model(type: WorkTimeResponse::class))
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
            )
        ]
    )]
    public function getAll(
        ResolvedWorktimesRequestDTO $resolvedDto,
        WorkTimeQuery $handler,
    ): CollectionResponse {
        $parsedSort = $handler->getParsedSort($resolvedDto);

        $cachedData = $handler->getCachedData($resolvedDto, $parsedSort);

        return new CollectionResponse(
            $cachedData["items"],
            $resolvedDto->page,
            $resolvedDto->limit,
            $cachedData["total"],
            $parsedSort,
            Response::HTTP_OK,
        );
    }

    /**
     * @throws NotFoundHttpException
     * @throws NotFoundHttpException
     */
    #[Route('/api/worktime/{id}/', methods: ['GET'], format: 'json')]
    #[OA\Get(
        operationId: 'getWorkTime',
        summary: 'Get detailed work time slot information.',
        tags: ['Public: WorkTime'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Work time details',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: new Model(type: AbstractItemResponseDTO::class)),
                        new OA\Schema(
                            properties: [
                                new OA\Property(
                                    property: 'data',
                                    ref: new Model(type: WorkTimeResponse::class)
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Work time slot not found',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            )
        ]
    )]
    public function get(
        TrainerWorkTime $worktime,
        WorkTimeManager $manager,
        WorkTimeMapperInterface $mapper,
    ): ItemResponse {
        return new ItemResponse(
            data: $mapper->map($manager->getAvailable($worktime)),
            status: Response::HTTP_OK,
        );
    }
}
