<?php

namespace App\Controller\All;

use App\Response\OkResponse;
use App\Trainer\DTO\GetTypesTrainers;
use App\Trainer\Entity\Trainer;
use App\Trainer\Mapper\TrainerMapperInterface;
use App\Trainer\Query\TrainersQuery;
use App\Trainer\Repository\TrainerRepository;
use OpenApi\Attributes as OA;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class TrainerController extends AbstractController
{
    /**
     * @throws InvalidArgumentException
     */
    #[Route('api/trainers', methods: ['GET'], format: 'json')]
    #[OA\Parameter(name: 'trainingTypeId', in: 'query', example: 1)]
    #[OA\Parameter(name: 'sort', in: 'query', example: 'createdAt:ASC')]
    #[OA\Parameter(name: 'page', in: 'query', example: 1)]
    #[OA\Parameter(name: 'limit', in: 'query', example: 20)]
    #[OA\Tag(name: "All: Trainers")]
    public function getAll(
        Request $request,
        TrainerMapperInterface $mapper,
        TrainersQuery $handler,
        TrainerRepository $trainerRepo,
    ): OkResponse
    {
        $sortRaw = $request->query->get('sort', 'createdAt:ASC');
        $trainingTypeId = $request->query->get('trainingTypeId') ? (int) $request->query->get('trainingTypeId') : null;
        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 20);

        $queryDto = new GetTypesTrainers($trainingTypeId, $sortRaw, $page, $limit);

        $trainers = $handler->handle($queryDto);

        return new OkResponse(
            array_map(fn ($trainer) => $mapper->map($trainer), $trainers),
            $queryDto->page,
            $queryDto->limit,
            $trainerRepo->count(),
            $queryDto->sort,
            200,
        );
    }

    #[Route('api/trainers/{id}', methods: ['GET'], format: 'json')]
    #[OA\Tag(name: "All: Trainers")]
    public function get(Trainer $trainer, TrainerMapperInterface $mapper): OkResponse
    {
        return new OkResponse(
            data: $mapper->map($trainer),
            status: 200,
        );
    }
}
