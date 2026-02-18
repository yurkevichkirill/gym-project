<?php

namespace App\Controller\Trainer;

use App\Response\OkResponse;
use App\Trainer\DTO\UpdateTrainerRequest;
use App\Trainer\Entity\Trainer;
use App\Trainer\Mapper\TrainerMapperInterface;
use App\Trainer\Repository\TrainerRepository;
use App\Trainer\Service\TrainerManager;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Throwable;

final class TrainerController extends AbstractController
{
    #[Route('api/trainer/me', methods: ['GET'], format: 'json')]
    #[OA\Tag(name: "Trainer: Trainer")]
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

    #[Route('api/trainer/me', methods: ['PUT', 'PATCH'], format: 'json')]
    #[OA\RequestBody(content: new Model(type: UpdateTrainerRequest::class))]
    #[OA\Tag(name: "Trainer: Trainer")]
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

//    #[Route('api/trainer/me}', methods: ['DELETE'], format: 'json')]
//    #[OA\Tag(name: "Trainer: Trainer")]
//    public function remove(Trainer $trainer, TrainerRepository $repo): JsonResponse
//    {
//        try {
//            $repo->remove($trainer);
//        } catch(Throwable $e) {
//            return $this->json(['error' => $e->getMessage()], 400);
//        }
//
//        return $this->json(null, 204);
//    }
}
