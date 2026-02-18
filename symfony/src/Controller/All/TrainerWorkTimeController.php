<?php

namespace App\Controller\All;

use App\Response\OkResponse;
use App\Trainer\Entity\Trainer;
use App\Trainer\Repository\TrainerRepository;
use App\TrainerWorkTime\DTO\GetTrainerWorkTime;
use App\TrainerWorkTime\Entity\TrainerWorkTime;
use App\TrainerWorkTime\Mapper\WorkTimeMapperInterface;
use App\TrainerWorkTime\Query\WorkTimeQuery;
use App\TrainerWorkTime\Repository\TrainerWorkTimeRepository;
use DateMalformedStringException;
use DateTimeImmutable;
use OpenApi\Attributes as OA;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class TrainerWorkTimeController extends AbstractController
{
    /**
     * @throws DateMalformedStringException
     * @throws InvalidArgumentException
     */
    #[Route('api/trainers/{id}/worktime', methods: ['GET'], format: 'json')]
    #[OA\Parameter(name: 'date', in: 'query', example: '10-03-2026')]
    #[OA\Parameter(name: 'sort', in: 'query', example: 'date:ASC')]
    #[OA\Parameter(name: 'page', in: 'query', example: 1)]
    #[OA\Parameter(name: 'limit', in: 'query', example: 20)]
    #[OA\Tag(name: "All: WorkTime")]
    public function getAll(
        Request $request,
        WorkTimeMapperInterface $mapper,
        WorkTimeQuery $handler,
        Trainer $trainer,
        TrainerWorkTimeRepository $worktimeRepo,
        TrainerRepository $trainerRepo,
    ): OkResponse
    {
        $id = $trainer->getId();
        $sortRaw = $request->query->get('sort', 'date:ASC');
        $date = $request->query->get('date') ? new DateTimeImmutable($request->query->get('date')) : null;
        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 20);

        $queryDto = new GetTrainerWorkTime($id, $date, $sortRaw, $page, $limit);

        $worktimes = $handler->handle($queryDto);

        return new OkResponse(
            array_map(fn ($worktime) => $mapper->map($worktime), $worktimes),
            $page,
            $limit,
            $worktimeRepo->count(['trainer' => $trainerRepo->find($id)]),
            $queryDto->sort,
            200,
        );
    }

    #[Route('api/worktime/{id}', methods: ['GET'], format: 'json')]
    #[OA\Tag(name: "All: WorkTime")]
    public function get(
        TrainerWorkTime $worktime,
        WorkTimeMapperInterface $mapper,
    ): OkResponse
    {
        return new OkResponse(
            $mapper->map($worktime),
            200,
        );
    }
}
