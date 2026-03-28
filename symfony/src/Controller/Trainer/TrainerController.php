<?php

namespace App\Controller\Trainer;

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
    #[OA\Tag(name: "OurTrainer: OurTrainer")]
    public function get(
        #[CurrentUser] Trainer                    $trainer,
        TrainerMapperInterface                    $mapper,
    ): OkResponse
    {
        $responseDto = $mapper->map($trainer, true);

        return new OkResponse(
            data: $responseDto,
            status: 200,
        );
    }

    #[Route('/api/trainer/me/', methods: ['PUT', 'PATCH'], format: 'json')]
    #[OA\RequestBody(content: new Model(type: UpdateTrainerRequest::class))]
    #[OA\Tag(name: "OurTrainer: OurTrainer")]
    public function update(
        #[CurrentUser] Trainer                    $trainer,
        #[MapRequestPayload] UpdateTrainerRequest $requestDto,
        TrainerMapperInterface                    $mapper,
        TrainerManager                            $manager,
    ): OkResponse
    {
        $responseDto = $mapper->map($manager->update($trainer, $requestDto), true);

        return new OkResponse(
            data: $responseDto,
            status: 200,
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Route('/api/trainer/me/', methods: ['DELETE'], format: 'json')]
    #[IsGranted('ROLE_TRAINER')]
    #[OA\Tag(name: "OurTrainer: OurTrainer")]
    public function remove(
        #[CurrentUser] Trainer $trainer,
        TrainerManager $manager,
    ): Response
    {
        $manager->softDelete($trainer);
        $this->container->get('security.token_storage')->setToken(null);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
