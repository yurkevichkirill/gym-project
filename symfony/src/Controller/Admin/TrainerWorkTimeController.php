<?php

namespace App\Controller\Admin;

use App\Response\ItemResponse;
use App\Response\NoContentResponse;
use App\Trainer\Entity\Trainer;
use App\TrainerWorkTime\DTO\CreateWorkTimeRequest;
use App\TrainerWorkTime\DTO\UpdateWorkTimeRequest;
use App\TrainerWorkTime\DTO\WorkTimeResponse;
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
            content: new OA\JsonContent(ref: new Model(type: CreateWorkTimeRequest::class))
        ),
        tags: ['Admin: WorkTime'],
        parameters: [
            new OA\Parameter(name: 'id', description: 'Trainer ID', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Work time created successfully.',
                content: new OA\JsonContent(ref: new Model(type: WorkTimeResponse::class))
            ),
            new OA\Response(
                response: 400,
                description: 'Bad Request - Cannot create worktime in the past.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Cannot create worktime in the past'),
                        new OA\Property(property: 'request_id', type: 'string', nullable: true)
                    ]
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden - Trainer is blocked.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'User is blocked')
                    ]
                )
            ),
            new OA\Response(
                response: 404,
                description: 'Trainer not found.'
            ),
            new OA\Response(
                response: 409,
                description: 'Conflict - Worktime already exists for this date.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Trainer already have worktime in this date')
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation failed')
        ]
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function create(
        Trainer                                    $trainer,
        #[MapRequestPayload] CreateWorkTimeRequest $requestDto,
        WorkTimeMapperInterface                    $mapper,
        WorkTimeManager                            $manager,
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
    #[Route('/api/admin/worktime/{id}/', methods: ['PUT', 'PATCH'], format: 'json')]
    #[OA\Put(
        operationId: 'adminUpdateWorkTime',
        summary: 'Update work time slot (Admin).',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: UpdateWorkTimeRequest::class))
        ),
        tags: ['Admin: WorkTime'],
        parameters: [
            new OA\Parameter(name: 'id', description: 'WorkTime ID', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Work time updated successfully.',
                content: new OA\JsonContent(ref: new Model(type: WorkTimeResponse::class))
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden - Access denied.'
            ),
            new OA\Response(
                response: 404,
                description: 'Work time slot not found.'
            ),
            new OA\Response(
                response: 409,
                description: 'Conflict - New interval intersects with existing trainings.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'OurTrainer already have training in this time')
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Validation failed')
        ]
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function update(
        TrainerWorkTime $worktime,
        #[MapRequestPayload] UpdateWorkTimeRequest $requestDto,
        WorkTimeMapperInterface $mapper,
        WorkTimeManager $manager,
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
                description: 'Work time slot removed.'
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized.'
            ),
            new OA\Response(
                response: 409,
                description: 'Conflict - Cannot remove work time with active bookings.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'This date already taken')
                    ]
                )
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
