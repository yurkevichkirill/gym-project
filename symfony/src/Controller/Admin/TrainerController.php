<?php

namespace App\Controller\Admin;

use App\Admin\Entity\Admin;
use App\Response\CollectionResponse;
use App\Response\ItemResponse;
use App\Response\NoContentResponse;
use App\Trainer\DTO\AdminUpdateTrainerRequest;
use App\Trainer\DTO\CreateTrainerRequest;
use App\Trainer\DTO\TrainerResponsePrivate;
use App\Trainer\Entity\Trainer;
use App\Trainer\Factory\GetTrainersFactory;
use App\Trainer\Mapper\TrainerMapperInterface;
use App\Trainer\Query\TrainersQuery;
use App\Trainer\Service\TrainerManager;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Psr\Cache\InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class TrainerController extends AbstractController
{
    /**
     * @throws InvalidArgumentException
     */
    #[Route('/api/admin/trainers/', methods: ['GET'], format: 'json')]
    #[OA\Get(
        operationId: 'adminGetTrainers',
        summary: 'Get all trainers with detailed info (Admin).',
        tags: ['Admin: Trainer'],
        parameters: [
            new OA\Parameter(name: 'minPricePerHour', in: 'query', schema: new OA\Schema(type: 'integer'), example: 30),
            new OA\Parameter(name: 'maxPricePerHour', in: 'query', schema: new OA\Schema(type: 'integer'), example: 50),
            new OA\Parameter(name: 'trainingTypeId', in: 'query', schema: new OA\Schema(type: 'integer'), example: 1),
            new OA\Parameter(name: 'sort', in: 'query', schema: new OA\Schema(type: 'string'), example: 'lastName:ASC'),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'items', type: 'array', items: new OA\Items(ref: new Model(type: TrainerResponsePrivate::class))),
                        new OA\Property(property: 'total', type: 'integer'),
                        new OA\Property(property: 'page', type: 'integer'),
                        new OA\Property(property: 'limit', type: 'integer'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized')
        ]
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function getAll(
        Request $request,
        TrainerMapperInterface $mapper,
        TrainersQuery $handler,
        GetTrainersFactory $factory,
    ): CollectionResponse {
        $queryDto = $factory->fromRequest($request);
        $trainers = $handler->handle($queryDto);

        return new CollectionResponse(
            array_map(fn ($trainer) => $mapper->map($trainer, true), $trainers),
            $queryDto->page,
            $queryDto->limit,
            $handler->getTotal($queryDto->filter),
            $queryDto->sort,
            Response::HTTP_OK,
        );
    }

    #[Route('/api/admin/trainers/{id}/', methods: ['GET'], format: 'json')]
    #[OA\Get(
        operationId: 'adminGetTrainer',
        summary: 'Get detailed trainer profile (Admin).',
        tags: ['Admin: Trainer'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: new Model(type: TrainerResponsePrivate::class))
            ),
            new OA\Response(response: 404, description: 'Trainer not found')
        ]
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function get(Trainer $trainer, TrainerMapperInterface $mapper): ItemResponse
    {
        return new ItemResponse(
            data: $mapper->map($trainer, true),
            status: Response::HTTP_OK,
        );
    }

    #[Route('/api/trainers/', methods: ['POST'], format: 'json')]
    #[OA\Post(
        operationId: 'adminCreateTrainer',
        summary: 'Create a new trainer (Admin).',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: new Model(type: CreateTrainerRequest::class))
        ),
        tags: ['Admin: Trainer'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Trainer created successfully.',
                content: new OA\JsonContent(ref: new Model(type: TrainerResponsePrivate::class))
            ),
            new OA\Response(
                response: 400,
                description: 'Bad Request - Training type not found',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')])
            ),
            new OA\Response(response: 422, description: 'Validation failed')
        ]
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function create(
        #[MapRequestPayload] CreateTrainerRequest $requestDto,
        TrainerMapperInterface $mapper,
        TrainerManager $manager,
    ): ItemResponse {
        $responseDto = $mapper->map($manager->create($requestDto), true);

        return new ItemResponse(
            data: $responseDto,
            status: Response::HTTP_OK,
        );
    }

    #[Route('/api/trainers/{id}/', methods: ['PATCH'], format: 'json')]
    #[OA\Patch(
        operationId: 'adminUpdateTrainer',
        summary: 'Update trainer profile (Admin).',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: new Model(type: AdminUpdateTrainerRequest::class))
        ),
        tags: ['Admin: Trainer'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Trainer updated successfully.',
                content: new OA\JsonContent(ref: new Model(type: TrainerResponsePrivate::class))
            ),
            new OA\Response(response: 404, description: 'Trainer not found'),
            new OA\Response(response: 422, description: 'Validation failed')
        ]
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function update(
        Trainer $trainer,
        #[MapRequestPayload] AdminUpdateTrainerRequest $requestDto,
        TrainerManager $manager,
        TrainerMapperInterface $mapper,
    ): ItemResponse {
        $responseDto = $mapper->map($manager->updateByAdmin($requestDto, $trainer), true);

        return new ItemResponse(
            data: $responseDto,
            status: Response::HTTP_OK,
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[Route('/api/trainers/{id}/', methods: ['DELETE'], format: 'json')]
    #[OA\Delete(
        operationId: 'adminDeleteTrainer',
        summary: 'Soft delete a trainer (Admin).',
        tags: ['Admin: Trainer'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 204, description: 'Trainer deleted successfully.'),
            new OA\Response(response: 403, description: 'Forbidden - Cannot delete yourself'),
            new OA\Response(
                response: 409,
                description: 'Conflict - Already deleted',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')])
            )
        ]
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(
        #[CurrentUser] Admin $admin,
        Trainer $trainer,
        TrainerManager $manager,
    ): NoContentResponse {
        $manager->softDelete($trainer, $admin);
        $this->container->get('security.token_storage')->setToken(null);

        return new NoContentResponse();
    }

    #[Route('/api/trainers/{id}/restore/', methods: ['POST'], format: 'json')]
    #[OA\Post(
        operationId: 'adminRestoreTrainer',
        summary: 'Restore a deleted trainer account.',
        tags: ['Admin: Trainer'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Trainer restored successfully.',
                content: new OA\JsonContent(ref: new Model(type: TrainerResponsePrivate::class))
            ),
            new OA\Response(response: 404, description: 'Trainer not found')
        ]
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function restore(
        Trainer $trainer,
        TrainerManager $manager,
        TrainerMapperInterface $mapper,
    ): ItemResponse {
        $responseDto = $mapper->map($manager->restore($trainer), true);

        return new ItemResponse(
            data: $responseDto,
            status: Response::HTTP_OK,
        );
    }

    #[Route('/api/trainers/{id}/block/', methods: ['POST'], format: 'json')]
    #[OA\Post(
        operationId: 'adminBlockTrainer',
        summary: 'Block trainer account.',
        tags: ['Admin: Trainer'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Trainer blocked successfully.',
                content: new OA\JsonContent(ref: new Model(type: TrainerResponsePrivate::class))
            ),
            new OA\Response(response: 403, description: 'Forbidden - Cannot block yourself'),
            new OA\Response(
                response: 409,
                description: 'Conflict - Already blocked',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'message', type: 'string')])
            )
        ]
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function block(
        #[CurrentUser] Admin $admin,
        Trainer $trainer,
        TrainerMapperInterface $mapper,
        TrainerManager $manager,
    ): ItemResponse {
        $responseDto = $mapper->map($manager->block($admin, $trainer), true);

        return new ItemResponse(
            data: $responseDto,
            status: Response::HTTP_OK,
        );
    }

    #[Route('/api/trainers/{id}/unblock/', methods: ['POST'], format: 'json')]
    #[OA\Post(
        operationId: 'adminUnblockTrainer',
        summary: 'Unblock trainer account.',
        tags: ['Admin: Trainer'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Trainer unblocked successfully.',
                content: new OA\JsonContent(ref: new Model(type: TrainerResponsePrivate::class))
            )
        ]
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function unblock(
        Trainer $trainer,
        TrainerMapperInterface $mapper,
        TrainerManager $manager,
    ): ItemResponse {
        $responseDto = $mapper->map($manager->unblock($trainer), true);

        return new ItemResponse(
            data: $responseDto,
            status: Response::HTTP_OK,
        );
    }
}
