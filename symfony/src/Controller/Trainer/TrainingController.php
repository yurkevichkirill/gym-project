<?php

namespace App\Controller\Trainer;

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
use App\User\Entity\User;
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
    #[Route('/api/me/trainings/', methods: ['GET'], format: 'json')]
    #[OA\Get(
        operationId: 'getTrainerTrainings',
        summary: 'Get current trainer trainings list.',
        tags: ['Trainer: Training'],
        parameters: [
            new OA\Parameter(name: 'clientId', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string'), example: 'scheduled'),
            new OA\Parameter(name: 'date', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
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
    #[IsGranted('ROLE_TRAINER')]
    public function getAll(
        TrainingMapperInterface $mapper,
        #[CurrentUser] Trainer $trainer,
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

    #[Route('/api/trainings/{id}/', methods: ['GET'], format: 'json')]
    #[OA\Get(
        operationId: 'getTrainingDetails',
        summary: 'Get training details.',
        tags: ['Trainer: Training'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: new Model(type: TrainingResponse::class))
            ),
            new OA\Response(response: 403, description: 'Forbidden - Not your training'),
            new OA\Response(response: 404, description: 'Training not found')
        ]
    )]
    #[IsGranted('ROLE_TRAINER')]
    public function get(
        TrainingMapperInterface $mapper,
        Training $training,
    ): ItemResponse {
        $this->denyAccessUnlessGranted("TRAINING_VIEW", $training);

        return new ItemResponse(
            data: $mapper->map($training),
            status: Response::HTTP_OK,
        );
    }

    /**
     * @throws HttpExceptionInterface
     * @throws DateMalformedStringException
     * @throws Throwable
     * @throws DateMalformedIntervalStringException
     */
    #[Route('/api/trainings/{id}/', methods: ['PUT', 'PATCH'], format: 'json')]
    #[OA\Patch(
        operationId: 'updateTraining',
        summary: 'Update/Reschedule training.',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: new Model(type: TrainingUpdateRequest::class))
        ),
        tags: ['Trainer: Training'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Training updated successfully.',
                content: new OA\JsonContent(ref: new Model(type: TrainingResponse::class))
            ),
            new OA\Response(
                response: 400,
                description: 'Bad Request - Date in past or less than 24h before start.',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')])
            ),
            new OA\Response(
                response: 409,
                description: 'Conflict - Only scheduled trainings can be updated.',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')])
            ),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 422, description: 'Validation failed')
        ]
    )]
    #[IsGranted('ROLE_TRAINER')]
    public function update(
        Training                                   $training,
        #[MapRequestPayload] TrainingUpdateRequest $requestDto,
        TrainingMapperInterface                    $mapper,
        TrainingManager                            $manager,
    ): ItemResponse {
        $this->denyAccessUnlessGranted("TRAINING_EDIT", $training);

        $responseDto = $mapper->map($manager->update($training, $requestDto));

        return new ItemResponse(
            data: $responseDto,
            status: Response::HTTP_OK,
        );
    }

    #[Route('/api/trainings/{id}/cancel/', methods: ['POST'], format: 'json')]
    #[OA\Post(
        operationId: 'cancelTraining',
        summary: 'Cancel training/booking.',
        tags: ['Trainer: Training'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 204, description: 'Training cancelled successfully.'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Training not found')
        ]
    )]
    #[IsGranted('ROLE_TRAINER')]
    public function cancel(
        Training $training,
        #[CurrentUser] User $actor,
        BookingCancellationService $bookingCancellationService,
    ): NoContentResponse {
        $this->denyAccessUnlessGranted("TRAINING_REMOVE", $training);
        $bookingCancellationService->cancel($training->getBooking(), $actor);

        return new NoContentResponse();
    }

    /**
     * @throws HttpExceptionInterface
     * @throws Throwable
     */
    #[Route('/api/trainings/{id}/complete/', methods: ['POST'], format: 'json')]
    #[OA\Post(
        operationId: 'completeTraining',
        summary: 'Mark training as completed.',
        tags: ['Trainer: Training'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Training completed successfully.',
                content: new OA\JsonContent(ref: new Model(type: TrainingResponse::class))
            ),
            new OA\Response(
                response: 400,
                description: 'Bad Request - Training has not happened yet.',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')])
            ),
            new OA\Response(response: 409, description: 'Conflict - Status is not scheduled')
        ]
    )]
    #[IsGranted('ROLE_TRAINER')]
    public function complete(
        Training $training,
        TrainingMapperInterface $mapper,
        TrainingManager $manager,
    ): ItemResponse {
        $this->denyAccessUnlessGranted("TRAINING_EDIT", $training);

        $responseDto = $mapper->map($manager->complete($training));

        return new ItemResponse(
            data: $responseDto,
            status: Response::HTTP_OK,
        );
    }
}
