<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Admin\Entity\Admin;
use App\Response\ResponseTypeDTO\CollectionResponse;
use App\Response\ResponseTypeDTO\ItemResponse;
use App\Response\ResponseTypeDTO\NoContentResponse;
use App\Response\SwaggerDocDTO\AbstractCollectionResponseDTO;
use App\Response\SwaggerDocDTO\AbstractItemResponseDTO;
use App\Response\SwaggerDocDTO\ErrorResponseDTO;
use App\Trainer\DTO\AdminUpdateTrainerRequestDTO;
use App\Trainer\DTO\CreateTrainerRequestDTO;
use App\Trainer\DTO\ResolvedTrainersRequestAdminDTO;
use App\Trainer\DTO\TrainerResponsePrivateDTO;
use App\Trainer\Entity\Trainer;
use App\Trainer\Mapper\TrainerMapperInterface;
use App\Trainer\Query\TrainersQueryAdmin;
use App\Trainer\Service\TrainerManager;
use App\User\Enum\UserRolesEnum;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

final class TrainerController extends AbstractController
{
    /**
     * @throws InvalidArgumentException
     * @throws BadRequestHttpException
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
            new OA\Parameter(name: 'minBalance', in: 'query', schema: new OA\Schema(type: 'integer'), example: 0),
            new OA\Parameter(name: 'maxBalance', in: 'query', schema: new OA\Schema(type: 'integer'), example: 10000),
            new OA\Parameter(name: 'isDeleted', in: 'query', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'isBlocked', in: 'query', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'sort', in: 'query', schema: new OA\Schema(type: 'string'), example: 'lastName:ASC'),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 20)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Collection of trainers',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: new Model(type: AbstractCollectionResponseDTO::class)),
                        new OA\Schema(
                            properties: [
                                new OA\Property(
                                    property: 'data',
                                    type: 'array',
                                    items: new OA\Items(ref: new Model(type: TrainerResponsePrivateDTO::class))
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Invalid query parameters',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 404,
                description: 'Training type not found',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            )
        ]
    )]
    #[IsGranted(UserRolesEnum::ROLE_ADMIN->value)]
    public function getAll(
        ResolvedTrainersRequestAdminDTO $resolvedDto,
        TrainersQueryAdmin $handler,
    ): CollectionResponse {
        $parsedSort = $handler->getParsedSort($resolvedDto);

        $cachedData = $handler->getCachedData($resolvedDto, $parsedSort);

        return new CollectionResponse(
            $cachedData['items'],
            $resolvedDto->page,
            $resolvedDto->limit,
            $cachedData['total'],
            $parsedSort,
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
                description: 'Trainer details',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: new Model(type: AbstractItemResponseDTO::class)),
                        new OA\Schema(
                            properties: [
                                new OA\Property(
                                    property: 'data',
                                    ref: new Model(type: TrainerResponsePrivateDTO::class)
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 404,
                description: 'Trainer not found',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            )
        ]
    )]
    #[IsGranted(UserRolesEnum::ROLE_ADMIN->value)]
    public function get(Trainer $trainer, TrainerMapperInterface $mapper): ItemResponse
    {
        return new ItemResponse(
            data: $mapper->map($trainer, true),
            status: Response::HTTP_OK,
        );
    }

    /**
     * @throws NotFoundHttpException
     * @throws Throwable
     */
    #[Route('/api/trainers/', methods: ['POST'], format: 'json')]
    #[OA\Post(
        operationId: 'adminCreateTrainer',
        summary: 'Create a new trainer (Admin).',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: new Model(type: CreateTrainerRequestDTO::class))
        ),
        tags: ['Admin: Trainer'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Trainer successfully created',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: new Model(type: AbstractItemResponseDTO::class)),
                        new OA\Schema(
                            properties: [
                                new OA\Property(
                                    property: 'data',
                                    ref: new Model(type: TrainerResponsePrivateDTO::class)
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Bad Request (e.g. Invalid training type or malformed data)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden (Insufficient admin rights)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 404,
                description: 'Not Found (Training type with provided ID not found)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 409,
                description: 'Conflict (User with this email or phone already exists)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error (Payload format or constraint violations)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            )
        ]
    )]
    #[IsGranted(UserRolesEnum::ROLE_ADMIN->value)]
    public function create(
        #[MapRequestPayload] CreateTrainerRequestDTO $requestDto,
        TrainerMapperInterface                       $mapper,
        TrainerManager                               $manager,
    ): ItemResponse {
        $responseDto = $mapper->map($manager->create($requestDto), true);

        return new ItemResponse(
            data: $responseDto,
            status: Response::HTTP_CREATED,
        );
    }

    /**
     * @throws ConflictHttpException
     */
    #[Route('/api/trainers/{id}/', methods: ['PATCH'], format: 'json')]
    #[OA\Patch(
        operationId: 'adminUpdateTrainer',
        summary: 'Update trainer profile (Admin).',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: new Model(type: AdminUpdateTrainerRequestDTO::class))
        ),
        tags: ['Admin: Trainer'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Trainer updated successfully',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: new Model(type: AbstractItemResponseDTO::class)),
                        new OA\Schema(
                            properties: [
                                new OA\Property(
                                    property: 'data',
                                    ref: new Model(type: TrainerResponsePrivateDTO::class)
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Bad Request (e.g. Invalid data format)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden (Insufficient admin rights)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 404,
                description: 'Not Found (Trainer not found)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 409,
                description: 'Conflict (Email or phone already taken by another user)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 422,
                description: 'Validation failed',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            )
        ]
    )]
    #[IsGranted(UserRolesEnum::ROLE_ADMIN->value)]
    public function update(
        Trainer                                           $trainer,
        #[MapRequestPayload] AdminUpdateTrainerRequestDTO $requestDto,
        TrainerManager                                    $manager,
        TrainerMapperInterface                            $mapper,
    ): ItemResponse {
        $responseDto = $mapper->map($manager->updateByAdmin($requestDto, $trainer), true);

        return new ItemResponse(
            data: $responseDto,
            status: Response::HTTP_OK,
        );
    }

    /**
     * @throws AccessDeniedHttpException
     * @throws ConflictHttpException
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
            new OA\Response(
                response: 204,
                description: 'Trainer deleted successfully'
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden (Insufficient admin rights or trying to delete yourself)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 404,
                description: 'Not Found (Trainer with this ID does not exist)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 409,
                description: 'Conflict (Trainer is already deleted)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            )
        ]
    )]
    #[IsGranted(UserRolesEnum::ROLE_ADMIN->value)]
    public function delete(
        #[CurrentUser] Admin $admin,
        Trainer $trainer,
        TrainerManager $manager,
    ): NoContentResponse {
        $manager->softDelete($trainer, $admin);

        return new NoContentResponse();
    }

    #[Route('/api/trainers/{id}/restore/', methods: ['POST'], format: 'json')]
    #[OA\Post(
        operationId: 'adminRestoreTrainer',
        summary: 'Restore a deleted trainer account (Admin).',
        tags: ['Admin: Trainer'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Trainer restored successfully',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: new Model(type: AbstractItemResponseDTO::class)),
                        new OA\Schema(
                            properties: [
                                new OA\Property(
                                    property: 'data',
                                    ref: new Model(type: TrainerResponsePrivateDTO::class)
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden (Insufficient admin rights)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 404,
                description: 'Not Found (Trainer not found)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 409,
                description: 'Conflict (Trainer is not currently deleted)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            )
        ]
    )]
    #[IsGranted(UserRolesEnum::ROLE_ADMIN->value)]
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

    /**
     * @throws AccessDeniedHttpException
     * @throws ConflictHttpException
     */
    #[Route('/api/trainers/{id}/block/', methods: ['POST'], format: 'json')]
    #[OA\Post(
        operationId: 'adminBlockTrainer',
        summary: 'Block a trainer account (Admin).',
        tags: ['Admin: Trainer'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Trainer blocked successfully',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: new Model(type: AbstractItemResponseDTO::class)),
                        new OA\Schema(
                            properties: [
                                new OA\Property(
                                    property: 'data',
                                    ref: new Model(type: TrainerResponsePrivateDTO::class)
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden (Insufficient admin rights or trying to block yourself)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 404,
                description: 'Not Found (Trainer not found)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 409,
                description: 'Conflict (Trainer is already blocked)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            )
        ]
    )]
    #[IsGranted(UserRolesEnum::ROLE_ADMIN->value)]
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

    /**
     * @throws ConflictHttpException
     */
    #[Route('/api/trainers/{id}/unblock/', methods: ['POST'], format: 'json')]
    #[OA\Post(
        operationId: 'adminUnblockTrainer',
        summary: 'Unblock a trainer account (Admin).',
        tags: ['Admin: Trainer'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Trainer unblocked successfully',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: new Model(type: AbstractItemResponseDTO::class)),
                        new OA\Schema(
                            properties: [
                                new OA\Property(
                                    property: 'data',
                                    ref: new Model(type: TrainerResponsePrivateDTO::class)
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden (Insufficient admin rights)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 404,
                description: 'Not Found (Trainer not found)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 409,
                description: 'Conflict (Trainer is not currently blocked)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            )
        ]
    )]
    #[IsGranted(UserRolesEnum::ROLE_ADMIN->value)]
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
