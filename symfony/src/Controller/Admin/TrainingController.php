<?php

namespace App\Controller\Admin;

use App\Response\CollectionResponse;
use App\Response\ItemResponse;
use App\Response\NoContentResponse;
use App\Response\OkResponse;
use App\Trainer\Entity\Trainer;
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
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class TrainingController extends AbstractController
{
    /**
     * @throws InvalidArgumentException
     */
    #[Route('/api/trainer/trainings/', methods: ['GET'], format: 'json')]
    #[OA\Parameter(name: 'clientId', in: 'query', example: 6)]
    #[OA\Parameter(name: 'status', in: 'query', example: 'scheduled')]
    #[OA\Parameter(name: 'date', in: 'query', example: '2026-03-10')]
    #[OA\Parameter(name: 'startTime', in: 'query', example: '15:00:00')]
    #[OA\Parameter(name: 'durationMinutes', in: 'query', example: 90)]
    #[OA\Parameter(name: 'sort', in: 'query', example: 'bookedAt:ASC')]
    #[OA\Parameter(name: 'page', in: 'query', example: 1)]
    #[OA\Parameter(name: 'limit', in: 'query', example: 20)]
    #[OA\Tag(name: "Admin: Training")]
    #[IsGranted('ROLE_TRAINER')]
    public function getAll(
        TrainingMapperInterface $mapper,
        TrainingsQuery $handler,
        Request $request,
        GetTrainingsFactory $factory,
    ): CollectionResponse
    {
        $queryDto = $factory->fromRequest(
            request: $request,
        );

        $trainings = $handler->handle($queryDto);

        return new CollectionResponse(
            array_map(fn ($training) => $mapper->map($training), $trainings),
            $queryDto->page,
            $queryDto->limit,
            $handler->getTotal($queryDto->filter),
            $queryDto->sort,
            Response:: HTTP_OK,
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    #[Route('/api/trainer/{id}/trainings/', methods: ['GET'], format: 'json')]
    #[OA\Parameter(name: 'clientId', in: 'query', example: 6)]
    #[OA\Parameter(name: 'status', in: 'query', example: 'scheduled')]
    #[OA\Parameter(name: 'date', in: 'query', example: '2026-03-10')]
    #[OA\Parameter(name: 'startTime', in: 'query', example: '15:00:00')]
    #[OA\Parameter(name: 'durationMinutes', in: 'query', example: 90)]
    #[OA\Parameter(name: 'sort', in: 'query', example: 'bookedAt:ASC')]
    #[OA\Parameter(name: 'page', in: 'query', example: 1)]
    #[OA\Parameter(name: 'limit', in: 'query', example: 20)]
    #[OA\Tag(name: "Admin: Training")]
    #[IsGranted('ROLE_TRAINER')]
    public function getAllByTrainer(
        TrainingMapperInterface $mapper,
        Trainer $trainer,
        TrainingsQuery $handler,
        Request $request,
        GetTrainingsFactory $factory,
    ): CollectionResponse
    {
        $queryDto = $factory->fromRequest(
            request: $request,
            trainer: $trainer,
        );

        $trainings = $handler->handle($queryDto);

        return new CollectionResponse(
            array_map(fn ($training) => $mapper->map($training), $trainings),
            $queryDto->page,
            $queryDto->limit,
            $handler->getTotal($queryDto->filter),
            $queryDto->sort,
            Response:: HTTP_OK,
        );
    }

    /**
     * @throws DateMalformedStringException
     * @throws DateMalformedIntervalStringException
     */
    #[Route('/api/admin/trainings/{id}/', methods: ['PUT', 'PATCH'], format: 'json')]
    #[OA\RequestBody(content: new Model(type: TrainingUpdateRequest::class))]
    #[OA\Tag(name: "Admin: Training")]
    #[IsGranted('ROLE_ADMIN')]
    public function update(
        Training                                   $training,
        #[MapRequestPayload] TrainingUpdateRequest $requestDto,
        TrainingMapperInterface                    $mapper,
        TrainingManager                            $manager,
    ): ItemResponse
    {
        $responseDto = $mapper->map($manager->update($training, $requestDto));

        return new ItemResponse(
            data: $responseDto,
            status: Response::HTTP_OK,
        );
    }

    #[Route('/api/admin/trainings/{id}/cancel/', methods: ['POST'], format: 'json')]
    #[OA\Tag(name: "Admin: Training")]
    #[IsGranted('ROLE_ADMIN')]
    public function cancel(
        Training $training,
        TrainingManager $trainingManager,
    ): NoContentResponse
    {
        $trainingManager->cancel($training, true);

        return new NoContentResponse();
    }

    #[Route('/api/admin/trainings/{id}/complete/', methods: ['POST'], format: 'json')]
    #[OA\Tag(name: "Admin: Training")]
    #[IsGranted('ROLE_ADMIN')]
    public function complete(
        Training $training,
        TrainingMapperInterface $mapper,
        TrainingManager $manager,
    ): ItemResponse
    {
        $responseDto = $mapper->map($manager->complete($training));

        return new ItemResponse(
            data: $responseDto,
            status: Response::HTTP_OK,
        );
    }
}
