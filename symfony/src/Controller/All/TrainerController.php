<?php

namespace App\Controller\All;

use App\Response\CollectionResponse;
use App\Response\ItemResponse;
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
use Symfony\Component\HttpFoundation\Response;
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
    ): CollectionResponse
    {
        $queryDto = $factory->fromRequest($request);

        $trainers = $handler->handle($queryDto);

        return new CollectionResponse(
            array_map(fn ($trainer) => $mapper->map($trainer), $trainers),
            $queryDto->page,
            $queryDto->limit,
            $handler->getTotal($queryDto->filter),
            $queryDto->sort,
            Response::HTTP_OK,
        );
    }

    #[Route('/api/trainers/{id}/', methods: ['GET'], format: 'json')]
    #[OA\Tag(name: "All: Trainers")]
    public function get(Trainer $trainer, TrainerMapperInterface $mapper): ItemResponse
    {
        return new ItemResponse(
            data: $mapper->map($trainer),
            status: Response::HTTP_OK,
        );
    }
}
