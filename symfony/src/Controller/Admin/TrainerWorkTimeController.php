<?php

namespace App\Controller\Admin;

use App\Response\ItemResponse;
use App\Response\NoContentResponse;
use App\Response\OkResponse;
use App\Trainer\Entity\Trainer;
use App\TrainerWorkTime\DTO\CreateWorkTimeRequest;
use App\TrainerWorkTime\DTO\UpdateWorkTimeRequest;
use App\TrainerWorkTime\Entity\TrainerWorkTime;
use App\TrainerWorkTime\Mapper\WorkTimeMapperInterface;
use App\TrainerWorkTime\Service\WorkTimeManager;
use DateMalformedIntervalStringException;
use DateMalformedStringException;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

final class TrainerWorkTimeController extends AbstractController
{
    /**
     * @throws DateMalformedStringException|Throwable
     */
    #[Route('/api/trainers/{id}/worktime/', methods: ['POST'], format: 'json')]
    #[OA\RequestBody(content: new Model(type: CreateWorkTimeRequest::class))]
    #[OA\Tag(name: "Admin: WorkTime")]
    #[IsGranted('ROLE_ADMIN')]
    public function create(
        Trainer                                    $trainer,
        #[MapRequestPayload] CreateWorkTimeRequest $requestDto,
        WorkTimeMapperInterface                    $mapper,
        WorkTimeManager                            $manager,
    ): ItemResponse
    {
        $responseDto = $mapper->map($manager->create($trainer, $requestDto));

        return new ItemResponse(
            data: $responseDto,
            status: Response::HTTP_CREATED,
        );
    }

    /**
     * @throws DateMalformedStringException
     * @throws DateMalformedIntervalStringException|Throwable
     */
    #[Route('/api/admin/worktime/{id}/', methods: ['PUT', 'PATCH'], format: 'json')]
    #[OA\RequestBody(content: new Model(type: UpdateWorkTimeRequest::class))]
    #[OA\Tag(name: "Admin: WorkTime")]
    #[IsGranted('ROLE_ADMIN')]
    public function update(
        TrainerWorkTime $worktime,
        #[MapRequestPayload] UpdateWorkTimeRequest $requestDto,
        WorkTimeMapperInterface $mapper,
        WorkTimeManager $manager,
    ): ItemResponse
    {
        $responseDto = $mapper->map($manager->update($worktime, $requestDto, true));

        return new ItemResponse(
            data: $responseDto,
            status: Response::HTTP_OK,
        );
    }

    /**
     * @throws Throwable
     */
    #[Route('/api/admin/worktime/{id}/', methods: ['DELETE'], format: 'json')]
    #[OA\Tag(name: "Admin: WorkTime")]
    #[IsGranted('ROLE_ADMIN')]
    public function remove(
        TrainerWorkTime $worktime,
        WorkTimeManager $manager,
    ): NoContentResponse
    {
        $manager->remove($worktime);

        return new NoContentResponse();
    }
}
