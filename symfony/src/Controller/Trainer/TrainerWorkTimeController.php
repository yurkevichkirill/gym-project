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
use App\TrainerWorkTime\Factory\GetTrainerWorkTimeFactory;
use App\TrainerWorkTime\Mapper\WorkTimeMapperInterface;
use App\TrainerWorkTime\Query\WorkTimeQuery;
use App\TrainerWorkTime\Service\WorkTimeManager;
use DateMalformedIntervalStringException;
use DateMalformedStringException;
use Nelmio\ApiDocBundle\Attribute\Model;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class TrainerWorkTimeController extends AbstractController
{
    /**
     * @throws InvalidArgumentException
     */
    #[Route('/api/trainer/me/worktime/', methods: ['GET'], format: 'json')]
    #[OA\Tag(name: "Trainer: WorkTime")]
    #[OA\Parameter(name: 'date', in: 'query', example: '2026-03-10')]
    #[OA\Parameter(name: 'sort', in: 'query', example: 'date:ASC')]
    #[OA\Parameter(name: 'page', in: 'query', example: 1)]
    #[OA\Parameter(name: 'limit', in: 'query', example: 20)]
    #[IsGranted('ROLE_TRAINER')]
    public function getAll(
        #[CurrentUser] Trainer $trainer,
        Request $request,
        WorkTimeMapperInterface $mapper,
        WorkTimeQuery $handler,
        GetTrainerWorkTimeFactory $factory,
    ): OkResponse
    {
        $queryDto = $factory->fromRequest(
            request: $request,
            trainer: $trainer,
        );

        $worktimes = $handler->handle($queryDto);

        return new OkResponse(
            array_map(fn ($worktime) => $mapper->map($worktime), $worktimes),
            $queryDto->page,
            $queryDto->limit,
            $handler->getTotal($queryDto->filter),
            $queryDto->sort,
            Response::HTTP_OK,
        );
    }

    /**
     * @throws DateMalformedStringException
     * @throws DateTimeAlreadyTakenException
     */
    #[Route('/api/trainer/me/worktime/', methods: ['POST'], format: 'json')]
    #[OA\RequestBody(content: new Model(type: CreateWorkTimeRequest::class))]
    #[OA\Tag(name: "Trainer: WorkTime")]
    #[IsGranted('ROLE_TRAINER')]
    public function create(
        #[CurrentUser] Trainer                     $trainer,
        #[MapRequestPayload] CreateWorkTimeRequest $requestDto,
        WorkTimeMapperInterface                    $mapper,
        WorkTimeManager                            $manager
    ): OkResponse
    {
        $responseDto = $mapper->map($manager->create($trainer, $requestDto));

        return new OkResponse(
            data: $responseDto,
            status:Response::HTTP_CREATED,
        );
    }

    /**
     * @throws DateMalformedStringException
     * @throws DateMalformedIntervalStringException
     * @throws DateTimeAlreadyTakenException
     */
    #[Route('/api/worktime/{id}/', methods: ['PUT', 'PATCH'], format: 'json')]
    #[OA\RequestBody(content: new Model(type: UpdateWorkTimeRequest::class))]
    #[OA\Tag(name: "Trainer: WorkTime")]
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
            status: Response::HTTP_OK,
        );
    }

    #[Route('/api/worktime/{id}/', methods: ['DELETE'], format: 'json')]
    #[OA\Tag(name: "Trainer: WorkTime")]
    public function remove(
        TrainerWorkTime $worktime,
        WorkTimeManager $manager,
    ): Response
    {
        $this->denyAccessUnlessGranted("WORKTIME_REMOVE", $worktime);
        $manager->remove($worktime);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
