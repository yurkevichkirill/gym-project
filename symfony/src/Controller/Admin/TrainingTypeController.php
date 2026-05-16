<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Response\DTO\AbstractItemResponseDTO;
use App\Response\DTO\ErrorResponseDTO;
use App\Response\ItemResponse;
use App\Response\NoContentResponse;
use App\TrainingType\DTO\CreateTrainingTypeRequest;
use App\TrainingType\DTO\TrainingTypeResponseDto;
use App\TrainingType\DTO\UpdateTrainingTypeRequest;
use App\TrainingType\Entity\TrainingType;
use App\TrainingType\Mapper\TrainingTypeMapperInterface;
use App\TrainingType\Service\TrainingTypeManager;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class TrainingTypeController extends AbstractController
{
    #[Route('/api/training/types/', methods: ['POST'], format: 'json')]
    #[OA\Post(
        operationId: 'adminCreateTrainingType',
        summary: 'Create a new training type (Admin).',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: CreateTrainingTypeRequest::class))
        ),
        tags: ['Admin: TrainingType'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Training type created successfully',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: new Model(type: AbstractItemResponseDTO::class)),
                        new OA\Schema(
                            properties: [
                                new OA\Property(
                                    property: 'data',
                                    ref: new Model(type: TrainingTypeResponseDto::class)
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
                response: 422,
                description: 'Validation failed (Invalid input data)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            )
        ]
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function create(
        #[MapRequestPayload] CreateTrainingTypeRequest $requestDto,
        TrainingTypeManager $manager,
        TrainingTypeMapperInterface $mapper,
    ): ItemResponse {
        $responseDto = $mapper->map($manager->create($requestDto));

        return new ItemResponse(
            data: $responseDto,
            status: Response::HTTP_CREATED,
        );
    }

    #[Route('/api/training/types/{id}/', methods: ['PATCH'], format: 'json')]
    #[OA\Patch(
        operationId: 'adminUpdateTrainingType',
        summary: 'Update an existing training type (Admin).',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: UpdateTrainingTypeRequest::class))
        ),
        tags: ['Admin: TrainingType'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Training type ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Training type updated successfully',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: new Model(type: AbstractItemResponseDTO::class)),
                        new OA\Schema(
                            properties: [
                                new OA\Property(
                                    property: 'data',
                                    ref: new Model(type: TrainingTypeResponseDto::class)
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Bad Request',
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
                description: 'Training type not found (or invalid ID format)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 422,
                description: 'Validation failed (Invalid input data)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            )
        ]
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function update(
        #[MapRequestPayload] UpdateTrainingTypeRequest $requestDto,
        TrainingType $trainingType,
        TrainingTypeManager $manager,
        TrainingTypeMapperInterface $mapper,
    ): ItemResponse {
        $responseDto = $mapper->map($manager->update($requestDto, $trainingType));

        return new ItemResponse(
            data: $responseDto,
            status: Response::HTTP_OK,
        );
    }

    #[Route('/api/training/types/{id}/', methods: ['DELETE'], format: 'json')]
    #[OA\Delete(
        operationId: 'adminDeleteTrainingType',
        summary: 'Delete a training type (Admin).',
        tags: ['Admin: TrainingType'],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Training type ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: 'Training type deleted successfully'
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
                description: 'Training type not found (or invalid ID format)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
        ]
    )]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(
        TrainingType $trainingType,
        TrainingTypeManager $manager,
    ): NoContentResponse {
        $manager->remove($trainingType);

        return new NoContentResponse();
    }
}
