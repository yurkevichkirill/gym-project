<?php

declare(strict_types=1);

namespace App\Controller\Trainer;

use App\Response\ResponseTypeDTO\CollectionResponse;
use App\Response\ResponseTypeDTO\ItemResponse;
use App\Response\ResponseTypeDTO\NoContentResponse;
use App\Response\SwaggerDocDTO\AbstractCollectionResponseDTO;
use App\Response\SwaggerDocDTO\AbstractItemResponseDTO;
use App\Response\SwaggerDocDTO\ErrorResponseDTO;
use App\Trainer\Entity\Trainer;
use App\TrainerWorkTime\DTO\CreateWorkTimeRequestDTO;
use App\TrainerWorkTime\DTO\ResolvedWorktimesRequestDTO;
use App\TrainerWorkTime\DTO\UpdateWorkTimeRequestDTO;
use App\TrainerWorkTime\DTO\WorkTimeResponseDTO;
use App\TrainerWorkTime\Entity\TrainerWorkTime;
use App\TrainerWorkTime\Mapper\WorkTimeMapperInterface;
use App\TrainerWorkTime\Query\WorkTimeQuery;
use App\TrainerWorkTime\Security\WorkTimeVoter;
use App\TrainerWorkTime\Service\WorkTimeManager;
use App\User\Enum\UserRolesEnum;
use DateMalformedIntervalStringException;
use DateMalformedStringException;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

final class TrainerWorkTimeController extends AbstractController
{
    /**
     * @throws InvalidArgumentException
     * @throws BadRequestHttpException
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
                description: 'List of trainer work time slots',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: new Model(type: AbstractCollectionResponseDTO::class)),
                        new OA\Schema(
                            properties: [
                                new OA\Property(
                                    property: 'data',
                                    type: 'array',
                                    items: new OA\Items(ref: new Model(type: WorkTimeResponseDTO::class))
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
                response: 401,
                description: 'Unauthorized',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            )
        ]
    )]
    #[IsGranted(UserRolesEnum::ROLE_TRAINER->value)]
    public function getAll(
        ResolvedWorktimesRequestDTO $resolvedDto,
        WorkTimeQuery $handler,
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
     * @throws DateMalformedStringException
     * @throws Throwable
     */
    #[Route('/api/trainer/me/worktime/', methods: ['POST'], format: 'json')]
    #[OA\Post(
        operationId: 'createTrainerWorkTime',
        summary: 'Create a new work time slot for current trainer.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: CreateWorkTimeRequestDTO::class))
        ),
        tags: ['Trainer: WorkTime'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Work time created successfully',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: new Model(type: AbstractItemResponseDTO::class)),
                        new OA\Schema(
                            properties: [
                                new OA\Property(
                                    property: 'data',
                                    ref: new Model(type: WorkTimeResponseDTO::class)
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Bad Request (e.g., Cannot create worktime in the past)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden (Trainer is blocked)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 409,
                description: 'Conflict (Worktime already exists for this date)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 422,
                description: 'Validation failed (Invalid input data)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            )
        ]
    )]
    #[IsGranted(UserRolesEnum::ROLE_TRAINER->value)]
    public function create(
        #[CurrentUser] Trainer                        $trainer,
        #[MapRequestPayload] CreateWorkTimeRequestDTO $requestDto,
        WorkTimeMapperInterface                       $mapper,
        WorkTimeManager                               $manager
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
    #[Route('/api/worktime/{id}/', methods: ['PATCH'], format: 'json')]
    #[OA\Patch(
        operationId: 'updateTrainerWorkTime',
        summary: 'Update existing work time slot.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: UpdateWorkTimeRequestDTO::class))
        ),
        tags: ['Trainer: WorkTime'],
        parameters: [
            new OA\Parameter(name: 'id', description: 'WorkTime ID', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Work time updated successfully',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: new Model(type: AbstractItemResponseDTO::class)),
                        new OA\Schema(
                            properties: [
                                new OA\Property(
                                    property: 'data',
                                    ref: new Model(type: WorkTimeResponseDTO::class)
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Bad Request (e.g., End time is before start time)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden (Not your work time slot or trainer is blocked)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 404,
                description: 'Work time slot not found',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 409,
                description: 'Conflict (Intersects with existing training)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 422,
                description: 'Validation failed (Invalid input data)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            )
        ]
    )]
    #[IsGranted(UserRolesEnum::ROLE_TRAINER->value)]
    public function update(
        TrainerWorkTime                               $worktime,
        #[MapRequestPayload] UpdateWorkTimeRequestDTO $requestDto,
        WorkTimeMapperInterface                       $mapper,
        WorkTimeManager                               $manager,
    ): ItemResponse {
        $this->denyAccessUnlessGranted(WorkTimeVoter::EDIT_OWN, $worktime);

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
            new OA\Parameter(name: 'id', description: 'WorkTime ID', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: 'Work time slot deleted successfully'
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden (Not your work time slot)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 404,
                description: 'Work time slot not found',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 409,
                description: 'Conflict (Cannot delete worktime with active trainings)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            )
        ]
    )]
    #[IsGranted(UserRolesEnum::ROLE_TRAINER->value)]
    public function remove(
        TrainerWorkTime $worktime,
        WorkTimeManager $manager,
    ): NoContentResponse {
        $this->denyAccessUnlessGranted(WorkTimeVoter::REMOVE_OWN, $worktime);
        $manager->remove($worktime);

        return new NoContentResponse();
    }
}
