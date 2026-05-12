<?php

namespace App\Controller\Public;

use App\Response\CollectionResponse;
use App\Response\ItemResponse;
use App\Response\OkResponse;
use App\Trainer\Entity\Trainer;
use App\TrainerWorkTime\Entity\TrainerWorkTime;
use App\TrainerWorkTime\Factory\GetTrainerWorkTimeFactory;
use App\TrainerWorkTime\Mapper\WorkTimeMapperInterface;
use App\TrainerWorkTime\Query\WorkTimeQuery;
use OpenApi\Attributes as OA;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\Routing\Attribute\Route;

final class TrainerWorkTimeController extends AbstractController
{
    /**
     * @throws InvalidArgumentException
     */
    #[Route('/api/worktime/', methods: ['GET'], format: 'json')]
    #[Cache(public: true)]
    #[OA\Parameter(name: 'date', in: 'query', example: '10-03-2026')]
    #[OA\Parameter(name: 'sort', in: 'query', example: 'date:ASC')]
    #[OA\Parameter(name: 'page', in: 'query', example: 1)]
    #[OA\Parameter(name: 'limit', in: 'query', example: 20)]
    #[OA\Tag(name: "All: WorkTime")]
    public function getAll(
        Request $request,
        WorkTimeMapperInterface $mapper,
        WorkTimeQuery $handler,
        GetTrainerWorkTimeFactory $factory,
    ): CollectionResponse
    {
        $queryDto = $factory->fromRequest(
            request: $request,
        );

        $worktimes = $handler->handle($queryDto);

        return new CollectionResponse(
            array_map(fn ($worktime) => $mapper->map($worktime), $worktimes),
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
    #[Route('/api/trainers/{id}/worktime/', methods: ['GET'], format: 'json')]
    #[Cache(public: true)]
    #[OA\Parameter(name: 'date', in: 'query', example: '10-03-2026')]
    #[OA\Parameter(name: 'sort', in: 'query', example: 'date:ASC')]
    #[OA\Parameter(name: 'page', in: 'query', example: 1)]
    #[OA\Parameter(name: 'limit', in: 'query', example: 20)]
    #[OA\Tag(name: "All: WorkTime")]
    public function getAllByTrainer(
        Request $request,
        WorkTimeMapperInterface $mapper,
        WorkTimeQuery $handler,
        Trainer $trainer,
        GetTrainerWorkTimeFactory $factory,
    ): CollectionResponse
    {
        $queryDto = $factory->fromRequest(
            request: $request,
            trainer: $trainer,
        );

        $worktimes = $handler->handle($queryDto);

        return new CollectionResponse(
            array_map(fn ($worktime) => $mapper->map($worktime), $worktimes),
            $queryDto->page,
            $queryDto->limit,
            $handler->getTotal($queryDto->filter),
            $queryDto->sort,
            Response::HTTP_OK,
        );
    }

    #[Route('/api/worktime/{id}/', methods: ['GET'], format: 'json')]
    #[OA\Tag(name: "All: WorkTime")]
    public function get(
        TrainerWorkTime $worktime,
        WorkTimeMapperInterface $mapper,
    ): ItemResponse
    {
        return new ItemResponse(
            $mapper->map($worktime),
            Response::HTTP_OK,
        );
    }
}
