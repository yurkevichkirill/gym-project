<?php

namespace App\Controller\Trainer;

use App\Exception\DateRescheduledException;
use App\Response\CollectionResponse;
use App\Response\ItemResponse;
use App\Response\NoContentResponse;
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
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class TrainingController extends AbstractController
{
    /**
     * @throws InvalidArgumentException
     */
    #[Route('/api/me/trainings/', methods: ['GET'], format: 'json')]
    #[OA\Parameter(name: 'clientId', in: 'query', example: 6)]
    #[OA\Parameter(name: 'status', in: 'query', example: 'scheduled')]
    #[OA\Parameter(name: 'date', in: 'query', example: '2026-03-10')]
    #[OA\Parameter(name: 'startTime', in: 'query', example: '15:00:00')]
    #[OA\Parameter(name: 'durationMinutes', in: 'query', example: 90)]
    #[OA\Parameter(name: 'isBusy', in: 'query', example: 'true')]
    #[OA\Parameter(name: 'sort', in: 'query', example: 'bookedAt:ASC')]
    #[OA\Parameter(name: 'page', in: 'query', example: 1)]
    #[OA\Parameter(name: 'limit', in: 'query', example: 20)]
    #[OA\Tag(name: "Trainer: Training")]
    #[IsGranted('ROLE_TRAINER')]
    public function getAll(
        TrainingMapperInterface $mapper,
        #[CurrentUser] Trainer $trainer,
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
            Response::HTTP_OK,
        );
    }

    #[Route('/api/trainings/{id}/', methods: ['GET'], format: 'json')]
    #[OA\Tag(name: "Trainer: Training")]
    public function get(
        TrainingMapperInterface $mapper,
        Training $training,
    ): ItemResponse
    {
        $this->denyAccessUnlessGranted("TRAINING_VIEW", $training);

        return new ItemResponse(
            data: $mapper->map($training),
            status: Response::HTTP_OK,
        );
    }

    /**
     * @throws DateRescheduledException
     * @throws DateMalformedStringException
     * @throws DateMalformedIntervalStringException
     */
    #[Route('/api/trainings/{id}/', methods: ['PUT', 'PATCH'], format: 'json')]
    #[OA\RequestBody(content: new Model(type: TrainingUpdateRequest::class))]
    #[OA\Tag(name: "Trainer: Training")]
    public function update(
        Training                                   $training,
        #[MapRequestPayload] TrainingUpdateRequest $requestDto,
        TrainingMapperInterface                    $mapper,
        TrainingManager                            $manager,
    ): ItemResponse
    {
        $this->denyAccessUnlessGranted("TRAINING_EDIT", $training);

        $responseDto = $mapper->map($manager->update($training, $requestDto));

        return new ItemResponse(
            data: $responseDto,
            status: Response::HTTP_OK,
        );
    }

    #[Route('/api/trainings/{id}/cancel/', methods: ['POST'], format: 'json')]
    #[OA\Tag(name: "Trainer: Training")]
    public function cancel(
        Training $training,
        TrainingManager $trainingManager,
    ): NoContentResponse
    {
        $this->denyAccessUnlessGranted("TRAINING_REMOVE", $training);
        $trainingManager->cancel($training);

        return new NoContentResponse();
    }

    #[Route('/api/trainings/{id}/complete/', methods: ['POST'], format: 'json')]
    #[OA\Tag(name: "Trainer: Training")]
    public function complete(
        Training $training,
        TrainingMapperInterface $mapper,
        TrainingManager $manager,
    ): ItemResponse
    {
        $this->denyAccessUnlessGranted("TRAINING_EDIT", $training);

        $responseDto = $mapper->map($manager->complete($training));

        return new ItemResponse(
            data: $responseDto,
            status: Response::HTTP_OK,
        );
    }
}
