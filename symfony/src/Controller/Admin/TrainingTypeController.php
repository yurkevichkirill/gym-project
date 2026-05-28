<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Response\ResponseTypeDTO\ItemResponse;
use App\Response\ResponseTypeDTO\NoContentResponse;
use App\Response\SwaggerDocDTO\AbstractItemResponseDTO;
use App\Response\SwaggerDocDTO\ErrorResponseDTO;
use App\TrainingType\DTO\CreateTrainingTypeRequestDTO;
use App\TrainingType\DTO\TrainingTypeResponseDTO;
use App\TrainingType\DTO\UpdateTrainingTypeRequestDTO;
use App\TrainingType\Entity\TrainingType;
use App\TrainingType\Mapper\TrainingTypeMapperInterface;
use App\TrainingType\Service\TrainingTypeManager;
use App\User\Enum\UserRolesEnum;
use League\Flysystem\FilesystemException;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Attribute\MapUploadedFile;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints as Assert;

final class TrainingTypeController extends AbstractController
{
    #[Route('/api/training/types/', methods: ['POST'], format: 'json')]
    #[OA\Post(
        operationId: 'adminCreateTrainingType',
        summary: 'Create a new training type (Admin).',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: CreateTrainingTypeRequestDTO::class))
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
                                    ref: new Model(type: TrainingTypeResponseDTO::class)
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
    #[IsGranted(UserRolesEnum::ROLE_ADMIN->value)]
    public function create(
        #[MapRequestPayload] CreateTrainingTypeRequestDTO $requestDto,
        TrainingTypeManager                               $manager,
        TrainingTypeMapperInterface                       $mapper,
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
            content: new OA\JsonContent(ref: new Model(type: UpdateTrainingTypeRequestDTO::class))
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
                                    ref: new Model(type: TrainingTypeResponseDTO::class)
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
    #[IsGranted(UserRolesEnum::ROLE_ADMIN->value)]
    public function update(
        #[MapRequestPayload] UpdateTrainingTypeRequestDTO $requestDto,
        TrainingType                                      $trainingType,
        TrainingTypeManager                               $manager,
        TrainingTypeMapperInterface                       $mapper,
    ): ItemResponse {
        $responseDto = $mapper->map($manager->update($requestDto, $trainingType));

        return new ItemResponse(
            data: $responseDto,
            status: Response::HTTP_OK,
        );
    }

    /**
     * @throws FilesystemException
     */
    #[Route('/api/training/types/{id}/photo/', methods: ['POST'])]
    #[OA\Post(
        operationId: 'adminUploadTrainingTypePhoto',
        summary: 'Upload or update a photo for a specific training type (Admin).',
        requestBody: new OA\RequestBody(
            description: 'Image file to upload',
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(
                            property: 'photo',
                            description: 'The photo file (jpeg, png, webp). Max size 2MB.',
                            type: 'string',
                            format: 'binary'
                        )
                    ]
                )
            )
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
                description: 'Training type photo uploaded successfully.',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: new Model(type: AbstractItemResponseDTO::class)),
                        new OA\Schema(
                            properties: [
                                new OA\Property(
                                    property: 'data',
                                    ref: new Model(type: TrainingTypeResponseDTO::class)
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
                description: 'Training type not found',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 422,
                description: 'Validation failed (e.g., file too large, wrong format)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            )
        ]
    )]
    #[IsGranted(UserRolesEnum::ROLE_ADMIN->value)]
    public function uploadPhoto(
        TrainingType $trainingType,
        #[MapUploadedFile([
            new Assert\NotNull(),
            new Assert\File(
                maxSize: '2M',
                mimeTypes: ['image/jpeg', 'image/png', 'image/webp']
            ),
            new Assert\Image(
                minWidth: 300,
                maxWidth: 2000,
                maxHeight: 3000
            ),
        ])]
        UploadedFile $photo,
        TrainingTypeManager $manager,
        TrainingTypeMapperInterface $mapper,
    ): ItemResponse {
        $responseDto = $mapper->map($manager->updatePhoto($trainingType, $photo));

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
    #[IsGranted(UserRolesEnum::ROLE_ADMIN->value)]
    public function delete(
        TrainingType $trainingType,
        TrainingTypeManager $manager,
    ): NoContentResponse {
        $manager->remove($trainingType);

        return new NoContentResponse();
    }
}
