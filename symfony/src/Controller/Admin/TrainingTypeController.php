<?php

namespace App\Controller\Admin;

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
                description: 'Training type created successfully.',
                content: new OA\JsonContent(ref: new Model(type: TrainingTypeResponseDto::class))
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 422, description: 'Validation failed')
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

    #[Route('/api/training/types/{id}/', methods: ['PATCH', 'PUT'], format: 'json')]
    #[OA\Put(
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
                description: 'Training Type ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Training type updated successfully.',
                content: new OA\JsonContent(ref: new Model(type: TrainingTypeResponseDto::class))
            ),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Training type not found'),
            new OA\Response(response: 422, description: 'Validation failed')
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
            status: Response::HTTP_CREATED,
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
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: 'Training type deleted successfully.'
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(
                response: 409,
                description: 'Conflict - Training type is currently assigned to trainers or sessions.',
                content: new OA\JsonContent(properties: [
                    new OA\Property(property: 'message', type: 'string', example: 'Cannot remove training type with active trainers')
                ])
            )
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
