<?php

declare(strict_types=1);

namespace App\Controller\Trainer;

use App\Response\ResponseTypeDTO\ItemResponse;
use App\Response\ResponseTypeDTO\NoContentResponse;
use App\Response\SwaggerDocDTO\AbstractItemResponseDTO;
use App\Response\SwaggerDocDTO\ErrorResponseDTO;
use App\Trainer\DTO\TrainerResponsePrivateDTO;
use App\Trainer\DTO\UpdateTrainerRequestDTO;
use App\Trainer\Entity\Trainer;
use App\Trainer\Mapper\TrainerMapperInterface;
use App\Trainer\Service\TrainerManager;
use App\User\Enum\UserRolesEnum;
use League\Flysystem\FilesystemException;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Attribute\MapUploadedFile;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints as Assert;

final class TrainerController extends AbstractController
{
    #[Route('/api/trainer/me/', methods: ['GET'], format: 'json')]
    #[OA\Get(
        operationId: 'getTrainerMe',
        summary: 'Get profile information of the current trainer.',
        tags: ['Trainer: Profile'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Current trainer profile retrieved successfully.',
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
                description: 'Forbidden - Trainer access required',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            )
        ]
    )]
    #[IsGranted(UserRolesEnum::ROLE_TRAINER->value)]
    public function get(
        #[CurrentUser] Trainer $trainer,
        TrainerMapperInterface $mapper,
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
            required: true,
            content: new OA\JsonContent(ref: new Model(type: UpdateTrainerRequestDTO::class))
        ),
        tags: ['Trainer: Profile'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Trainer profile updated successfully.',
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
                description: 'Invalid JSON payload',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden - Trainer access required',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 422,
                description: 'Validation failed',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            )
        ]
    )]
    #[IsGranted(UserRolesEnum::ROLE_TRAINER->value)]
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
     * @throws FilesystemException
     */
    #[Route('/api/trainer/me/photo/', methods: ['POST'])]
    #[OA\Post(
        operationId: 'uploadTrainerPhoto',
        summary: 'Upload or update current trainer photo.',
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
        tags: ['Trainer: Profile'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Trainer photo uploaded successfully.',
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
                response: 422,
                description: 'Validation failed (e.g., file too large, wrong format)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            )
        ]
    )]
    #[IsGranted(UserRolesEnum::ROLE_TRAINER->value)]
    public function uploadPhoto(
        #[CurrentUser] Trainer $trainer,
        #[MapUploadedFile([
            new Assert\NotNull,
            new Assert\File(
                maxSize: '2M',
                mimeTypes: ['image/jpeg', 'image/png', 'image/webp']
            ),
            new Assert\Image(
                minWidth: 300,
                maxWidth: 1500,
                maxHeight: 2000,
                minHeight: 400,
                maxRatio: 1.5,
                minRatio: 0.5,
            ),
        ])]
        UploadedFile $photo,
        TrainerManager $manager,
        TrainerMapperInterface $mapper,
    ): ItemResponse {
        $responseDto = $mapper->map($manager->updatePhoto($trainer, $photo), true);

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
        summary: 'Soft delete current trainer account and clear sessions.',
        tags: ['Trainer: Profile'],
        responses: [
            new OA\Response(
                response: 204,
                description: 'Account successfully deleted (No Content).'
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
                response: 409,
                description: 'Conflict - Account is already deleted',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            )
        ]
    )]
    #[IsGranted(UserRolesEnum::ROLE_TRAINER->value)]
    public function remove(
        #[CurrentUser] Trainer $trainer,
        TrainerManager $manager,
    ): NoContentResponse {
        $manager->softDelete($trainer);
        $this->container->get('security.token_storage')->setToken(null);

        return new NoContentResponse();
    }
}
