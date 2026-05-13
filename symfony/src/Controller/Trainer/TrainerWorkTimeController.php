<?php

declare(strict_types=1);

namespace App\Controller\Trainer;

use App\Response\CollectionResponse;
use App\Response\ItemResponse;
use App\Response\NoContentResponse;
use App\Trainer\Entity\Trainer;
use App\TrainerWorkTime\DTO\CreateWorkTimeRequest;
use App\TrainerWorkTime\DTO\UpdateWorkTimeRequest;
use App\TrainerWorkTime\DTO\WorkTimeResponse;
use App\TrainerWorkTime\Entity\TrainerWorkTime;
use App\TrainerWorkTime\Factory\GetTrainerWorkTimeFactory;
use App\TrainerWorkTime\Mapper\WorkTimeMapperInterface;
use App\TrainerWorkTime\Query\WorkTimeQuery;
use App\TrainerWorkTime\Service\WorkTimeManager;
use DateMalformedIntervalStringException;
use DateMalformedStringException;
use Nelmio\ApiDocBundle\Attribute\Model;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

final class TrainerWorkTimeController extends AbstractController
{
    /**
     * @throws InvalidArgumentException
     */
    #[Route('/api/trainer/me/worktime/', methods: ['GET'], format: 'json')]
    #[OA\Get(
        operationId: 'getTrainerWorkTime',
        summary: 'Get current trainer work time slots.',
        tags: ['Trainer: WorkTime'],
        parameters: [
            new OA\Parameter(name: 'date', in: 'query', schema: new OA\Schema(type: 'string', format: 'date'), example: '2026-03-10'),
            new OA\Parameter(name: 'sort', in: 'query', schema: new OA\Schema(type: 'string'), example: 'date:ASC'),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: new Model(type: WorkTimeResponse::class))),
                        new OA\Property(property: 'total', type: 'integer'),
                        new OA\Property(property: 'page', type: 'integer'),
                        new OA\Property(property: 'limit', type: 'integer'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized')
        ]
    )]
    #[IsGranted('ROLE_TRAINER')]
    public function getAll(
        #[CurrentUser] Trainer $trainer,
        Request $request,
        WorkTimeMapperInterface $mapper,
        WorkTimeQuery $handler,
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

    /**
     * @throws DateMalformedStringException
     * @throws Throwable
     */
    #[Route('/api/trainer/me/worktime/', methods: ['POST'], format: 'json')]
    #[OA\Post(
        operationId: 'createTrainerWorkTime',
        summary: 'Create a new work time slot for current trainer.',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: new Model(type: CreateWorkTimeRequest::class))
        ),
        tags: ['Trainer: WorkTime'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Work time created successfully.',
                content: new OA\JsonContent(ref: new Model(type: WorkTimeResponse::class))
            ),
            new OA\Response(
                response: 400,
                description: 'Bad Request - Cannot create worktime in the past.',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')])
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden - Trainer is blocked.',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')])
            ),
            new OA\Response(
                response: 409,
                description: 'Conflict - Worktime already exists for this date.',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')])
            ),
            new OA\Response(response: 422, description: 'Validation failed')
        ]
    )]
    #[IsGranted('ROLE_TRAINER')]
    public function create(
        #[CurrentUser] Trainer                     $trainer,
        #[MapRequestPayload] CreateWorkTimeRequest $requestDto,
        WorkTimeMapperInterface                    $mapper,
        WorkTimeManager                            $manager
    ): ItemResponse {
        $responseDto = $mapper->map($manager->create($trainer, $requestDto));

        return new ItemResponse(
            data: $responseDto,
            status: Response::HTTP_CREATED,
        );
    }

    /**
     * @throws DateMalformedStringException
     * @throws Throwable
     * @throws DateMalformedIntervalStringException
     */
    #[Route('/api/worktime/{id}/', methods: ['PUT', 'PATCH'], format: 'json')]
    #[OA\Put(
        operationId: 'updateTrainerWorkTime',
        summary: 'Update existing work time slot.',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: new Model(type: UpdateWorkTimeRequest::class))
        ),
        tags: ['Trainer: WorkTime'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Work time updated successfully.',
                content: new OA\JsonContent(ref: new Model(type: WorkTimeResponse::class))
            ),
            new OA\Response(
                response: 409,
                description: 'Conflict - Intersects with existing training.',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')])
            ),
            new OA\Response(response: 403, description: 'Forbidden - Not your worktime or trainer is blocked'),
            new OA\Response(response: 404, description: 'Worktime not found')
        ]
    )]
    #[IsGranted('ROLE_TRAINER')]
    public function update(
        TrainerWorkTime $worktime,
        #[MapRequestPayload] UpdateWorkTimeRequest $requestDto,
        WorkTimeMapperInterface $mapper,
        WorkTimeManager $manager,
    ): ItemResponse {
        $this->denyAccessUnlessGranted('WORKTIME_EDIT', $worktime);

        $responseDto = $mapper->map($manager->update($worktime, $requestDto));

        return new ItemResponse(
            data: $responseDto,
            status: Response::HTTP_OK,
        );
    }

    /**
     * @throws Throwable
     */
    #[Route('/api/worktime/{id}/', methods: ['DELETE'], format: 'json')]
    #[OA\Delete(
        operationId: 'deleteTrainerWorkTime',
        summary: 'Delete work time slot.',
        tags: ['Trainer: WorkTime'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 204, description: 'Work time slot deleted.'),
            new OA\Response(
                response: 409,
                description: 'Conflict - Cannot delete worktime with active trainings.',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')])
            ),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Worktime not found')
        ]
    )]
    #[IsGranted('ROLE_TRAINER')]
    public function remove(
        TrainerWorkTime $worktime,
        WorkTimeManager $manager,
    ): NoContentResponse {
        $this->denyAccessUnlessGranted("WORKTIME_REMOVE", $worktime);
        $manager->remove($worktime);

        return new NoContentResponse();
    }
}
