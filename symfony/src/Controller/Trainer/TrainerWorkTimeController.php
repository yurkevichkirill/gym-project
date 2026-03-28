<?php

declare(strict_types=1);

namespace App\Controller\Trainer;

use App\Exception\DateTimeAlreadyTakenException;
use App\Response\OkResponse;
use App\Trainer\Entity\Trainer;
use App\TrainerWorkTime\DTO\GetTrainerWorkTime;
use App\TrainerWorkTime\DTO\CreateWorkTimeRequest;
use App\TrainerWorkTime\DTO\UpdateWorkTimeRequest;
use App\TrainerWorkTime\Entity\TrainerWorkTime;
use App\TrainerWorkTime\Mapper\WorkTimeMapperInterface;
use App\TrainerWorkTime\Query\WorkTimeQuery;
use App\TrainerWorkTime\Repository\TrainerWorkTimeRepository;
use App\TrainerWorkTime\Service\WorkTimeManager;
use DateMalformedStringException;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Nelmio\ApiDocBundle\Attribute\Model;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class TrainerWorkTimeController extends AbstractController
{
    /**
     * @throws InvalidArgumentException
     */

    #[Route('/api/trainer/me/worktime/', methods: ['GET'], format: 'json')]
    #[OA\Tag(name: "OurTrainer: WorkTime")]
    #[OA\Parameter(name: 'date', in: 'query', example: '2026-03-10')]
    #[OA\Parameter(name: 'sort', in: 'query', example: 'date:ASC')]
    #[OA\Parameter(name: 'page', in: 'query', example: 1)]
    #[OA\Parameter(name: 'limit', in: 'query', example: 20)]
    public function getAll(
        #[CurrentUser] Trainer $trainer,
        Request $request,
        WorkTimeMapperInterface $mapper,
        WorkTimeQuery $handler,
    ): OkResponse
    {
        $sortRaw = $request->query->get('sort', 'date:ASC');
        $date = $request->query->get('date');
        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 20);

        $queryDto = new GetTrainerWorkTime($trainer, $date, $sortRaw, $page, $limit);

        $worktimes = $handler->handle($queryDto);

        return new OkResponse(
            array_map(fn ($worktime) => $mapper->map($worktime), $worktimes),
            $page,
            $limit,
            $handler->getTotal($queryDto->filter),
            $queryDto->sort,
            200,
        );
    }

    /**
     * @throws DateMalformedStringException
     * @throws DateTimeAlreadyTakenException
     */
    #[Route('/api/trainer/me/worktime/', methods: ['POST'], format: 'json')]
    #[OA\RequestBody(content: new Model(type: CreateWorkTimeRequest::class))]
    #[OA\Tag(name: "OurTrainer: WorkTime")]
    public function create(
        #[CurrentUser] Trainer                     $trainer,
        #[MapRequestPayload] CreateWorkTimeRequest $requestDto,
        WorkTimeMapperInterface                    $mapper,
        WorkTimeManager                            $manager
    ): OkResponse
    {
        $responseDto = $mapper->map($manager->create($trainer, $requestDto));

        return new OkResponse(
            data:$responseDto,
            status:201,
        );
    }

    /**
     * @throws DateMalformedStringException
     * @throws \DateMalformedIntervalStringException
     * @throws DateTimeAlreadyTakenException
     */
    #[Route('/api/worktime/{id}/', methods: ['PUT', 'PATCH'], format: 'json')]
    #[OA\RequestBody(content: new Model(type: UpdateWorkTimeRequest::class))]
    #[OA\Tag(name: "OurTrainer: WorkTime")]
    public function update(
        TrainerWorkTime $worktime,
        #[MapRequestPayload] UpdateWorkTimeRequest $requestDto,
        WorkTimeMapperInterface $mapper,
        WorkTimeManager $manager,
    ): OkResponse
    {
        $this->denyAccessUnlessGranted('WORKTIME_EDIT', $worktime);

        $responseDto = $mapper->map($manager->update($worktime, $requestDto));

        return new OkResponse(
            data: $responseDto,
            status: 200,
        );
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    #[Route('/api/worktime/{id}/', methods: ['DELETE'], format: 'json')]
    #[OA\Tag(name: "OurTrainer: WorkTime")]
    public function remove(
        TrainerWorkTime $worktime,
        TrainerWorkTimeRepository $worktimeRepo
    ): Response
    {
        $this->denyAccessUnlessGranted("WORKTIME_REMOVE", $worktime);
        $worktimeRepo->remove($worktime);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
