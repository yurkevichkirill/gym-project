<?php

namespace App\Controller\All;

use App\Response\OkResponse;
use App\Trainer\DTO\GetTrainers;
use App\Trainer\Entity\Trainer;
use App\Trainer\Factory\GetTrainersFactory;
use App\Trainer\Mapper\TrainerMapperInterface;
use App\Trainer\Query\TrainersQuery;
use App\TrainingType\Repository\TrainingTypeRepository;
use OpenApi\Attributes as OA;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\Routing\Attribute\Route;

final class TrainerController extends AbstractController
{
    /**
     * @throws InvalidArgumentException
     */
    #[Route('/api/trainers/', methods: ['GET'], format: 'json')]
    #[Cache(public: true)]
    #[OA\Parameter(name: 'minPricePerHour', in: 'query', example: 30)]
    #[OA\Parameter(name: 'maxPricePerHour', in: 'query', example: 50)]
    #[OA\Parameter(name: 'trainingTypeId', in: 'query', example: 1)]
    #[OA\Parameter(name: 'sort', in: 'query', example: 'lastName:ASC')]
    #[OA\Parameter(name: 'page', in: 'query', example: 1)]
    #[OA\Parameter(name: 'limit', in: 'query', example: 20)]
    #[OA\Tag(name: "All: Trainers")]
    public function getAll(
        Request $request,
        TrainerMapperInterface $mapper,
        TrainersQuery $handler,
        GetTrainersFactory $factory,
    ): OkResponse
    {
        $queryDto = $factory->fromRequest($request);

        $trainers = $handler->handle($queryDto);

        return new OkResponse(
            array_map(fn ($trainer) => $mapper->map($trainer), $trainers),
            $queryDto->page,
            $queryDto->limit,
            $handler->getTotal($queryDto->filter),
            $queryDto->sort,
            200,
        );
    }

    #[Route('/api/trainers/{id}/', methods: ['GET'], format: 'json')]
    #[OA\Tag(name: "All: Trainers")]
    public function get(Trainer $trainer, TrainerMapperInterface $mapper): OkResponse
    {
        return new OkResponse(
            data: $mapper->map($trainer),
            status: 200,
        );
    }
}
