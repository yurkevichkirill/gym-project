<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Response\ResponseTypeDTO\ItemResponse;
use App\Response\ResponseTypeDTO\NoContentResponse;
use App\Response\SwaggerDocDTO\AbstractItemResponseDTO;
use App\Response\SwaggerDocDTO\ErrorResponseDTO;
use App\Trainer\Entity\Trainer;
use App\TrainerWorkTime\DTO\CreateWorkTimeRequestDTO;
use App\TrainerWorkTime\DTO\UpdateWorkTimeRequestDTO;
use App\TrainerWorkTime\DTO\WorkTimeResponseDTO;
use App\TrainerWorkTime\Entity\TrainerWorkTime;
use App\TrainerWorkTime\Mapper\WorkTimeMapperInterface;
use App\TrainerWorkTime\Service\WorkTimeManager;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

final class TrainerWorkTimeController extends AbstractController
{
    /**
     * @throws Throwable
     */
    #[Route('/api/trainers/{id}/worktime/', methods: ['POST'], format: 'json')]
    #[OA\Post(
        operationId: 'adminCreateWorkTime',
        summary: 'Add work time slots for a trainer (Admin).',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: CreateWorkTimeRequestDTO::class))
        ),
        tags: ['Admin: WorkTime'],
        parameters: [
            new OA\Parameter(name: 'id', description: 'Trainer ID', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
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
                description: 'Forbidden (Trainer is blocked or insufficient admin rights)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 404,
                description: 'Trainer not found',
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
    #[IsGranted('ROLE_ADMIN')]
    public function create(
        Trainer                                       $trainer,
        #[MapRequestPayload] CreateWorkTimeRequestDTO $requestDto,
        WorkTimeMapperInterface                       $mapper,
        WorkTimeManager                               $manager,
    ): ItemResponse {
        $responseDto = $mapper->map($manager->create($trainer, $requestDto));

        return new ItemResponse(
            data: $responseDto,
            status: Response::HTTP_CREATED,
        );
    }

    /**
     * @throws Throwable
     */
    #[Route('/api/admin/worktime/{id}/', methods: ['PATCH'], format: 'json')]
    #[OA\Patch(
        operationId: 'adminUpdateWorkTime',
        summary: 'Update work time slot (Admin).',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: UpdateWorkTimeRequestDTO::class))
        ),
        tags: ['Admin: WorkTime'],
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
                description: 'Forbidden (Insufficient admin rights)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 404,
                description: 'Work time slot not found',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 409,
                description: 'Conflict (New interval intersects with existing trainings)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 422,
                description: 'Validation failed (Invalid input data)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            )
        ]
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function update(
        TrainerWorkTime                               $worktime,
        #[MapRequestPayload] UpdateWorkTimeRequestDTO $requestDto,
        WorkTimeMapperInterface                       $mapper,
        WorkTimeManager                               $manager,
    ): ItemResponse {
        $responseDto = $mapper->map($manager->update($worktime, $requestDto, true));

        return new ItemResponse(
            data: $responseDto,
            status: Response::HTTP_OK,
        );
    }

    /**
     * @throws Throwable
     */
    #[Route('/api/admin/worktime/{id}/', methods: ['DELETE'], format: 'json')]
    #[OA\Delete(
        operationId: 'adminDeleteWorkTime',
        summary: 'Remove work time slot (Admin).',
        tags: ['Admin: WorkTime'],
        parameters: [
            new OA\Parameter(name: 'id', description: 'WorkTime ID', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: 'Work time slot removed successfully'
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden (Insufficient admin rights)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 404,
                description: 'Work time slot not found',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 409,
                description: 'Conflict (Cannot remove work time with active bookings)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            )
        ]
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function remove(
        TrainerWorkTime $worktime,
        WorkTimeManager $manager,
    ): NoContentResponse {
        $manager->remove($worktime);

        return new NoContentResponse();
    }
}
