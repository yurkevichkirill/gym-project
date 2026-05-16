<?php

declare(strict_types=1);

namespace App\Controller\Trainer;

use App\Response\ResponseTypeDTO\ItemResponse;
use App\Response\ResponseTypeDTO\NoContentResponse;
use App\Trainer\DTO\TrainerResponsePrivateDTO;
use App\Trainer\DTO\UpdateTrainerRequestDTO;
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
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class TrainerController extends AbstractController
{
    #[Route('/api/trainer/me/', methods: ['GET'], format: 'json')]
    #[OA\Get(
        operationId: 'getTrainerMe',
        summary: 'Get current trainer profile details.',
        tags: ['Trainer: Trainer'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Current trainer profile retrieved successfully.',
                content: new OA\JsonContent(ref: new Model(type: TrainerResponsePrivateDTO::class))
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden - Trainer access required')
        ]
    )]
    #[IsGranted('ROLE_TRAINER')]
    public function get(
        #[CurrentUser] Trainer                    $trainer,
        TrainerMapperInterface                    $mapper,
    ): ItemResponse {
        $responseDto = $mapper->map($trainer, true);

        return new ItemResponse(
            data: $responseDto,
            status: Response::HTTP_OK,
        );
    }

    #[Route('/api/trainer/me/', methods: ['PATCH'], format: 'json')]
    #[OA\Patch(
        operationId: 'updateTrainerMe',
        summary: 'Update current trainer profile.',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: new Model(type: UpdateTrainerRequestDTO::class))
        ),
        tags: ['Trainer: Trainer'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Trainer profile updated successfully.',
                content: new OA\JsonContent(ref: new Model(type: TrainerResponsePrivateDTO::class))
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 422, description: 'Validation failed')
        ]
    )]
    #[IsGranted('ROLE_TRAINER')]
    public function update(
        #[CurrentUser] Trainer                       $trainer,
        #[MapRequestPayload] UpdateTrainerRequestDTO $requestDto,
        TrainerMapperInterface                       $mapper,
        TrainerManager                               $manager,
    ): ItemResponse {
        $responseDto = $mapper->map($manager->update($trainer, $requestDto), true);

        return new ItemResponse(
            data: $responseDto,
            status: Response::HTTP_OK,
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws AccessDeniedHttpException
     * @throws ConflictHttpException
     */
    #[Route('/api/trainer/me/', methods: ['DELETE'], format: 'json')]
    #[OA\Delete(
        operationId: 'deleteTrainerMe',
        summary: 'Deactivate (soft delete) current trainer account.',
        tags: ['Trainer: Trainer'],
        responses: [
            new OA\Response(response: 204, description: 'Account deactivated successfully.'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(
                response: 409,
                description: 'Conflict - Trainer already deleted',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')])
            )
        ]
    )]
    #[IsGranted('ROLE_TRAINER')]
    public function remove(
        #[CurrentUser] Trainer $trainer,
        TrainerManager $manager,
    ): NoContentResponse {
        $manager->softDelete($trainer);
        $this->container->get('security.token_storage')->setToken(null);

        return new NoContentResponse();
    }
}
