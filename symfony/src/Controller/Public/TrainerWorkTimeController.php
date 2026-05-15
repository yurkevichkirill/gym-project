<?php

namespace App\Controller\Public;

use App\Response\CollectionResponse;
use App\Response\ItemResponse;
use App\Trainer\Entity\Trainer;
use App\TrainerWorkTime\DTO\WorkTimeResponse;
use App\TrainerWorkTime\Entity\TrainerWorkTime;
use App\TrainerWorkTime\Factory\GetTrainerWorkTimeFactory;
use App\TrainerWorkTime\Mapper\WorkTimeMapperInterface;
use App\TrainerWorkTime\Query\WorkTimeQuery;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\Routing\Attribute\Route;

final class TrainerWorkTimeController extends AbstractController
{
    /**
     * @throws InvalidArgumentException
     */
    #[Route('/api/worktime/', methods: ['GET'], format: 'json')]
    #[Cache(maxage: 0, smaxage: 3600, public: true, mustRevalidate: true)]
    #[OA\Get(
        operationId: 'getWorkTimes',
        summary: 'Get all available work time slots.',
        tags: ['All: WorkTime'],
        parameters: [
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
                    properties: [
                        new OA\Property(
                            property: 'items',
                            type: 'array',
                            items: new OA\Items(ref: new Model(type: WorkTimeResponse::class))
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
        WorkTimeMapperInterface $mapper,
        WorkTimeQuery $handler,
        GetTrainerWorkTimeFactory $factory,
    ): CollectionResponse {
        $queryDto = $factory->fromRequest($request);
        $worktimes = $handler->handle($queryDto);

        return new CollectionResponse(
            array_map(fn ($worktime) => $mapper->map($worktime), $worktimes),
            $queryDto->page,
            $queryDto->limit,
            $handler->getTotal($queryDto->filter),
            $queryDto->sort,
            Response::HTTP_OK,
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    #[Route('/api/trainers/{id}/worktime/', methods: ['GET'], format: 'json')]
    #[Cache(maxage: 300, public: true, mustRevalidate: true)]
    #[OA\Get(
        operationId: 'getWorkTimesByTrainer',
        summary: 'Get work time slots for a specific trainer.',
        tags: ['All: WorkTime'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'date', in: 'query', schema: new OA\Schema(type: 'string', format: 'date'), example: '10-03-2026'),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Trainer work time slots',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'items',
                            type: 'array',
                            items: new OA\Items(ref: new Model(type: WorkTimeResponse::class))
                        )
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Trainer not found')
        ]
    )]
    public function getAllByTrainer(
        Request $request,
        WorkTimeMapperInterface $mapper,
        WorkTimeQuery $handler,
        Trainer $trainer,
        GetTrainerWorkTimeFactory $factory,
    ): CollectionResponse {
        $queryDto = $factory->fromRequest($request, $trainer);
        $worktimes = $handler->handle($queryDto);

        return new CollectionResponse(
            array_map(fn ($worktime) => $mapper->map($worktime), $worktimes),
            $queryDto->page,
            $queryDto->limit,
            $handler->getTotal($queryDto->filter),
            $queryDto->sort,
            Response::HTTP_OK,
        );
    }

    #[Route('/api/worktime/{id}/', methods: ['GET'], format: 'json')]
    #[OA\Get(
        operationId: 'getWorkTime',
        summary: 'Get detailed work time slot information.',
        tags: ['All: WorkTime'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Work time details',
                content: new OA\JsonContent(ref: new Model(type: WorkTimeResponse::class))
            ),
            new OA\Response(response: 404, description: 'Work time slot not found')
        ]
    )]
    public function get(
        TrainerWorkTime $worktime,
        WorkTimeMapperInterface $mapper,
    ): ItemResponse {
        return new ItemResponse(
            $mapper->map($worktime),
            Response::HTTP_OK,
        );
    }
}
