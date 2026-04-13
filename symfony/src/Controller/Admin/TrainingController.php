<?php

namespace App\Controller\Admin;

use App\Client\Repository\ClientRepository;
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
    #[Route('/api/trainings/', methods: ['GET'], format: 'json')]
    #[OA\Parameter(name: 'clientId', in: 'query', example: 6)]
    #[OA\Parameter(name: 'status', in: 'query', example: 'scheduled')]
    #[OA\Parameter(name: 'date', in: 'query', example: '2026-03-10')]
    #[OA\Parameter(name: 'startTime', in: 'query', example: '15:00:00')]
    #[OA\Parameter(name: 'durationMinutes', in: 'query', example: 90)]
    #[OA\Parameter(name: 'sort', in: 'query', example: 'bookedAt:ASC')]
    #[OA\Parameter(name: 'page', in: 'query', example: 1)]
    #[OA\Parameter(name: 'limit', in: 'query', example: 20)]
    #[OA\Tag(name: "Admin: Trainer")]
    #[IsGranted('ROLE_ADMIN')]
    public function getAll(
        TrainingMapperInterface $mapper,
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

        $queryDto = new GetTrainings($sortRaw, $client, $date, $durationMinutes, $startTime, $status, $page, $limit);

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

    /**
     * @throws InvalidArgumentException
     */
    #[Route('/api/trainers/{id}/trainings/', methods: ['GET'], format: 'json')]
    #[OA\Parameter(name: 'clientId', in: 'query', example: 6)]
    #[OA\Parameter(name: 'status', in: 'query', example: 'scheduled')]
    #[OA\Parameter(name: 'date', in: 'query', example: '2026-03-10')]
    #[OA\Parameter(name: 'startTime', in: 'query', example: '15:00:00')]
    #[OA\Parameter(name: 'durationMinutes', in: 'query', example: 90)]
    #[OA\Parameter(name: 'sort', in: 'query', example: 'bookedAt:ASC')]
    #[OA\Parameter(name: 'page', in: 'query', example: 1)]
    #[OA\Parameter(name: 'limit', in: 'query', example: 20)]
    #[OA\Tag(name: "Admin: Trainer")]
    #[IsGranted('ROLE_ADMIN')]
    public function getAllByTrainer(
        TrainingMapperInterface $mapper,
        Trainer $trainer,
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

        $queryDto = new GetTrainings($sortRaw, $client, $date, $durationMinutes, $startTime, $status, $page, $limit, $trainer);

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

    /**
     * @throws DateMalformedStringException
     * @throws DateMalformedIntervalStringException
     */
    #[Route('/api/admin/trainings/{id}/', methods: ['PUT', 'PATCH'], format: 'json')]
    #[OA\RequestBody(content: new Model(type: TrainingRequest::class))]
    #[OA\Tag(name: "Admin: Training")]
    #[IsGranted('ROLE_ADMIN')]
    public function update(
        Training $training,
        #[MapRequestPayload] TrainingRequest $requestDto,
        TrainingMapperInterface $mapper,
        TrainingManager $manager,
    ): OkResponse
    {
        $responseDto = $mapper->map($manager->update($training, $requestDto));

        return new OkResponse(
            data: $responseDto,
            status: 200,
        );
    }

    #[Route('/api/admin/trainings/{id}/', methods: ['DELETE'], format: 'json')]
    #[OA\Tag(name: "Admin: Training")]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(
        Training $training,
        TrainingManager $trainingManager,
    ): Response
    {
        $trainingManager->remove($training);

        return new Response(status: 204);
    }

    #[Route('/api/admin/trainings/{id}/complete/', methods: ['POST'], format: 'json')]
    #[OA\Tag(name: "Admin: Training")]
    #[IsGranted('ROLE_ADMIN')]
    public function complete(
        Training $training,
        TrainingMapperInterface $mapper,
        TrainingManager $manager,
    ): OkResponse
    {
        $responseDto = $mapper->map($manager->complete($training));

        return new OkResponse(
            data: $responseDto,
            status: 200,
        );
    }
}
