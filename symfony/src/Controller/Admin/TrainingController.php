<?php

namespace App\Controller\Admin;

use App\Booking\Service\BookingCancellationService;
use App\Response\CollectionResponse;
use App\Response\ItemResponse;
use App\Response\NoContentResponse;
use App\Trainer\Entity\Trainer;
use App\Training\DTO\TrainingResponse;
use App\Training\DTO\TrainingUpdateRequest;
use App\Training\Entity\Training;
use App\Training\Factory\GetTrainingsFactory;
use App\Training\Mapper\TrainingMapperInterface;
use App\Training\Query\TrainingsQuery;
use App\Training\Service\TrainingManager;
use DateMalformedIntervalStringException;
use DateMalformedStringException;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

final class TrainingController extends AbstractController
{
    /**
     * @throws InvalidArgumentException
     */
    #[Route('/api/trainer/trainings/', methods: ['GET'], format: 'json')]
    #[OA\Get(
        operationId: 'adminGetAllTrainings',
        summary: 'Get all trainings with filters (Admin).',
        tags: ['Admin: Training'],
        parameters: [
            new OA\Parameter(name: 'clientId', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string', example: 'scheduled')),
            new OA\Parameter(name: 'date', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'startTime', in: 'query', schema: new OA\Schema(type: 'string', format: 'time')),
            new OA\Parameter(name: 'durationMinutes', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'isBusy', in: 'query', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'sort', in: 'query', schema: new OA\Schema(type: 'string'), example: 'bookedAt:ASC'),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: new Model(type: TrainingResponse::class))),
                        new OA\Property(property: 'total', type: 'integer'),
                        new OA\Property(property: 'page', type: 'integer'),
                        new OA\Property(property: 'limit', type: 'integer'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized')
        ]
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function getAll(
        TrainingMapperInterface $mapper,
        TrainingsQuery $handler,
        Request $request,
        GetTrainingsFactory $factory,
    ): CollectionResponse {
        $queryDto = $factory->fromRequest($request);
        $trainings = $handler->handle($queryDto);

        return new CollectionResponse(
            array_map(fn ($training) => $mapper->map($training), $trainings),
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
    #[Route('/api/trainer/{id}/trainings/', methods: ['GET'], format: 'json')]
    #[OA\Get(
        operationId: 'adminGetTrainerTrainings',
        summary: 'Get trainings for a specific trainer (Admin).',
        tags: ['Admin: Training'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string', example: 'scheduled')),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Trainer trainings list',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: new Model(type: TrainingResponse::class)))
                ])
            ),
            new OA\Response(response: 404, description: 'Trainer not found')
        ]
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function getAllByTrainer(
        TrainingMapperInterface $mapper,
        Trainer $trainer,
        TrainingsQuery $handler,
        Request $request,
        GetTrainingsFactory $factory,
    ): CollectionResponse {
        $queryDto = $factory->fromRequest($request, $trainer);
        $trainings = $handler->handle($queryDto);

        return new CollectionResponse(
            array_map(fn ($training) => $mapper->map($training), $trainings),
            $queryDto->page,
            $queryDto->limit,
            $handler->getTotal($queryDto->filter),
            $queryDto->sort,
            Response::HTTP_OK,
        );
    }

    /**
     * @throws HttpExceptionInterface
     * @throws DateMalformedStringException
     * @throws Throwable
     * @throws DateMalformedIntervalStringException
     */
    #[Route('/api/admin/trainings/{id}/', methods: ['PUT', 'PATCH'], format: 'json')]
    #[OA\Put(
        operationId: 'adminUpdateTraining',
        summary: 'Update/Reschedule training details (Admin).',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: TrainingUpdateRequest::class))
        ),
        tags: ['Admin: Training'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Training updated successfully',
                content: new OA\JsonContent(ref: new Model(type: TrainingResponse::class))
            ),
            new OA\Response(
                response: 400,
                description: 'Bad Request - Date in past or rescheduling too late',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'The minimum reschedule date must be no earlier than tomorrow.')
                ])
            ),
            new OA\Response(response: 404, description: 'Training or trainer work time not found'),
            new OA\Response(
                response: 409,
                description: 'Conflict - Training is not in scheduled state',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Only scheduled trainings can be updated')
                ])
            ),
            new OA\Response(response: 422, description: 'Validation failed')
        ]
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function update(
        Training                                   $training,
        #[MapRequestPayload] TrainingUpdateRequest $requestDto,
        TrainingMapperInterface                    $mapper,
        TrainingManager                            $manager,
    ): ItemResponse {
        $responseDto = $mapper->map($manager->update($training, $requestDto));

        return new ItemResponse(
            data: $responseDto,
            status: Response::HTTP_OK,
        );
    }

    #[Route('/api/admin/trainings/{id}/cancel/', methods: ['POST'], format: 'json')]
    #[OA\Post(
        operationId: 'adminCancelTraining',
        summary: 'Cancel a training (Admin).',
        tags: ['Admin: Training'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 204, description: 'Training cancelled successfully'),
            new OA\Response(response: 403, description: 'Access Denied'),
            new OA\Response(response: 404, description: 'Training not found')
        ]
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function cancel(
        Training $training,
        #[CurrentUser] $actor,
        BookingCancellationService $bookingCancellationService,
    ): NoContentResponse {
        $bookingCancellationService->cancel($training->getBooking(), $actor);

        return new NoContentResponse();
    }

    /**
     * @throws HttpExceptionInterface
     * @throws Throwable
     */
    #[Route('/api/admin/trainings/{id}/complete/', methods: ['POST'], format: 'json')]
    #[OA\Post(
        operationId: 'adminCompleteTraining',
        summary: 'Mark training as completed (Admin).',
        tags: ['Admin: Training'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Training completed successfully',
                content: new OA\JsonContent(ref: new Model(type: TrainingResponse::class))
            ),
            new OA\Response(
                response: 400,
                description: 'Bad Request - Training has not happened yet',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Training has not happened yet')
                ])
            ),
            new OA\Response(
                response: 409,
                description: 'Conflict - Training state is not scheduled',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Only scheduled trainings can be completed')
                ])
            )
        ]
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function complete(
        Training $training,
        TrainingMapperInterface $mapper,
        TrainingManager $manager,
    ): ItemResponse {
        $responseDto = $mapper->map($manager->complete($training));

        return new ItemResponse(
            data: $responseDto,
            status: Response::HTTP_OK,
        );
    }
}
