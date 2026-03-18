<?php

namespace App\Controller\Trainer;

use App\Client\Repository\ClientRepository;
use App\Exception\DateRescheduledException;
use App\Response\OkResponse;
use App\Trainer\Entity\Trainer;
use App\Training\DTO\GetTrainings;
use App\Training\DTO\TrainingRequest;
use App\Training\Entity\Training;
use App\Training\Mapper\TrainingMapperInterface;
use App\Training\Query\TrainingsQuery;
use App\Training\Repository\TrainingRepository;
use App\Training\Service\TrainingManager;
use DateMalformedIntervalStringException;
use DateMalformedStringException;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class TrainingController extends AbstractController
{
    /**
     * @throws InvalidArgumentException
     */
    #[Route('api/trainer/me/trainings/', methods: ['GET'], format: 'json')]
    #[OA\Parameter(name: 'clientId', in: 'query', example: 6)]
    #[OA\Parameter(name: 'status', in: 'query', example: 'scheduled')]
    #[OA\Parameter(name: 'date', in: 'query', example: '2026-03-10')]
    #[OA\Parameter(name: 'startTime', in: 'query', example: '15:00:00')]
    #[OA\Parameter(name: 'durationMinutes', in: 'query', example: 90)]
    #[OA\Parameter(name: 'sort', in: 'query', example: 'bookedAt:ASC')]
    #[OA\Parameter(name: 'page', in: 'query', example: 1)]
    #[OA\Parameter(name: 'limit', in: 'query', example: 20)]
    #[OA\Tag(name: "OurTrainer: Training")]
    public function getAll(
        TrainingMapperInterface $mapper,
        #[CurrentUser] Trainer $trainer,
        TrainingsQuery   $handler,
        Request               $request,
        ClientRepository $clientRepo,
    ): OkResponse
    {
        $sortRaw = $request->query->get('sort', 'bookedAt:ASC');
        $client = $clientRepo->find((int) $request->query->get('clientId'));
        $date = $request->query->get('date');
        $durationMinutes = $request->query->get('durationMinutes') ? (int) $request->query->get('durationMinutes') : null;
        $startTime = $request->query->get('startTime');
        $status = $request->query->get('status');
        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 20);

        $queryDto = new GetTrainings($trainer, $sortRaw, $client, $date, $durationMinutes, $startTime, $status, $page, $limit);
        $trainings = $handler->handle($queryDto);

        return new OkResponse(
            array_map(fn ($training) => $mapper->map($training), $trainings),
            $queryDto->page,
            $queryDto->limit,
            $handler->getTotal($queryDto->filter),
            $queryDto->sort,
            200,
        );
    }

    #[Route('api/trainings/{id}/', methods: ['GET'], format: 'json')]
    #[OA\Tag(name: "OurTrainer: Training")]
    public function get(
        TrainingMapperInterface $mapper,
        Training $training,
    ): OkResponse
    {
        $this->denyAccessUnlessGranted("TRAINING_VIEW", $training);

        return new OkResponse(
            data: $mapper->map($training),
            status: 200,
        );
    }

    /**
     * @throws DateRescheduledException
     * @throws DateMalformedStringException
     * @throws DateMalformedIntervalStringException
     */
    #[Route('api/trainings/{id}/', methods: ['PUT', 'PATCH'], format: 'json')]
    #[OA\RequestBody(content: new Model(type: TrainingRequest::class))]
    #[OA\Tag(name: "OurTrainer: Training")]
    public function update(
        Training $training,
        #[MapRequestPayload] TrainingRequest $requestDto,
        TrainingMapperInterface $mapper,
        TrainingManager $manager,
    ): OkResponse
    {
        $this->denyAccessUnlessGranted("TRAINING_EDIT", $training);

        $responseDto = $mapper->map($manager->update($training, $requestDto));

        return new OkResponse(
            data: $responseDto,
            status: 200,
        );
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    #[Route('api/trainings/{id}/', methods: ['DELETE'], format: 'json')]
    #[OA\Tag(name: "OurTrainer: Training")]
    public function delete(
        Training $training,
        TrainingRepository $trainingRepo,
    ): Response
    {
        $this->denyAccessUnlessGranted("TRAINING_REMOVE", $training);
        $trainingRepo->remove($training);

        return new Response(status: 204);
    }
}
