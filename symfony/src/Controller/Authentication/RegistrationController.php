<?php
declare(strict_types=1);

namespace App\Controller\Authentication;

use App\Client\DTO\ClientResponse;
use App\Client\DTO\CreateClientRequest;
use App\Client\Mapper\ClientMapperInterface;
use App\Client\Service\ClientManager;
use App\Response\DTO\ErrorResponseDTO;
use App\Response\ItemResponse;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class RegistrationController extends AbstractController
{
    #[Route('/api/client/registration/', methods: ['POST'], format: 'json')]
    #[OA\Post(
        operationId: 'clientRegistration',
        summary: 'Register a new client.',
        tags: ['Authentication'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Client registered successfully.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            ref: new Model(type: ClientResponse::class),
                            type: 'object'
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Bad Request - Email or phone already exists.',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 422,
                description: 'Validation failed - Invalid DTO data.',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            )
        ]
    )]
    public function register(
        #[MapRequestPayload] CreateClientRequest $requestDto,
        ClientMapperInterface $mapper,
        ClientManager $manager,
    ): ItemResponse {
        $responseDto = $mapper->map($manager->create($requestDto));

        return new ItemResponse(
            data: $responseDto,
            status: Response::HTTP_OK,
        );
    }
}
