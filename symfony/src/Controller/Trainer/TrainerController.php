<?php

namespace App\Controller\Trainer;

use App\Response\ItemResponse;
use App\Response\NoContentResponse;
use App\Response\OkResponse;
use App\Trainer\DTO\UpdateTrainerRequest;
use App\Trainer\Entity\Trainer;
use App\Trainer\Mapper\TrainerMapperInterface;
use App\Trainer\Service\TrainerManager;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class TrainerController extends AbstractController
{
    #[Route('/api/trainer/me/', methods: ['GET'], format: 'json')]
    #[OA\Tag(name: "Trainer: Trainer")]
    #[IsGranted('ROLE_TRAINER')]
    public function get(
        #[CurrentUser] Trainer                    $trainer,
        TrainerMapperInterface                    $mapper,
    ): ItemResponse
    {
        $responseDto = $mapper->map($trainer, true);

        return new ItemResponse(
            data: $responseDto,
            status: Response::HTTP_OK,
        );
    }

    #[Route('/api/trainer/me/', methods: ['PUT', 'PATCH'], format: 'json')]
    #[OA\RequestBody(content: new Model(type: UpdateTrainerRequest::class))]
    #[OA\Tag(name: "Trainer: Trainer")]
    #[IsGranted('ROLE_TRAINER')]
    public function update(
        #[CurrentUser] Trainer                    $trainer,
        #[MapRequestPayload] UpdateTrainerRequest $requestDto,
        TrainerMapperInterface                    $mapper,
        TrainerManager                            $manager,
    ): ItemResponse
    {
        $responseDto = $mapper->map($manager->update($trainer, $requestDto), true);

        return new ItemResponse(
            data: $responseDto,
            status: Response::HTTP_OK,
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Route('/api/trainer/me/', methods: ['DELETE'], format: 'json')]
    #[OA\Tag(name: "Trainer: Trainer")]
    #[IsGranted('ROLE_TRAINER')]
    public function remove(
        #[CurrentUser] Trainer $trainer,
        TrainerManager $manager,
    ): NoContentResponse
    {
        $manager->softDelete($trainer);
        $this->container->get('security.token_storage')->setToken(null);

        return new NoContentResponse();
    }
}
