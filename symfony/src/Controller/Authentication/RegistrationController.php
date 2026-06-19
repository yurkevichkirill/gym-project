<?php
declare(strict_types=1);

namespace App\Controller\Authentication;

use App\Client\DTO\ClientResponseDTO;
use App\Client\DTO\CreateClientRequestDTO;
use App\Client\Mapper\ClientMapperInterface;
use App\Client\Service\ClientManager;
use App\Response\ResponseTypeDTO\ItemResponse;
use App\Response\SwaggerDocDTO\AbstractItemResponseDTO;
use App\Response\SwaggerDocDTO\ErrorResponseDTO;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class RegistrationController extends AbstractController
{
    /**
     * @throws ConflictHttpException
     */
    #[Route('/api/client/registration/', methods: ['POST'], format: 'json')]
    #[OA\Post(
        operationId: 'clientRegistration',
        summary: 'Register a new client.',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: new Model(type: CreateClientRequestDTO::class))
        ),
        tags: ['Authentication'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Client registered successfully',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: new Model(type: AbstractItemResponseDTO::class)),
                        new OA\Schema(
                            properties: [
                                new OA\Property(
                                    property: 'data',
                                    ref: new Model(type: ClientResponseDTO::class)
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Bad Request (e.g. Malformed JSON)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 409,
                description: 'Conflict (Email or phone number already exists)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 422,
                description: 'Validation failed (Invalid input data)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 429,
                description: 'Too many requests. Retry-After header contains the delay in seconds.',
                content: new OA\JsonContent(
                    ref: new Model(type: ErrorResponseDTO::class)
                ),
            ),
        ]
    )]
    public function register(
        #[MapRequestPayload] CreateClientRequestDTO $requestDto,
        ClientMapperInterface                       $mapper,
        ClientManager                               $manager,
    ): ItemResponse {
        $responseDto = $mapper->map($manager->create($requestDto));

        return new ItemResponse(
            data: $responseDto,
            status: Response::HTTP_CREATED,
        );
    }
}
