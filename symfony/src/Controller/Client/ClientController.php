<?php

declare(strict_types=1);

namespace App\Controller\Client;

use App\Client\DTO\ClientResponse;
use App\Client\DTO\TopUpBalanceRequest;
use App\Client\DTO\UpdateClientRequest;
use App\Client\Entity\Client;
use App\Client\Mapper\ClientMapperInterface;
use App\Client\Service\ClientManager;
use App\Membership\DTO\MembershipResponse;
use App\Membership\Exception\NoActiveMembershipException;
use App\Membership\Mapper\MembershipMapperInterface;
use App\Payment\DTO\PaymentResponse;
use App\Payment\Mapper\PaymentMapperInterface;
use App\Response\DTO\AbstractItemResponseDTO;
use App\Response\DTO\ErrorResponseDTO;
use App\Response\ItemResponse;
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

final class ClientController extends AbstractController
{
    #[Route('/api/me/', methods: ['GET'], format: 'json')]
    #[OA\Get(
        operationId: 'getCurrentClient',
        summary: 'Get profile information of the current client.',
        tags: ['Client: Profile'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Client profile data.',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: new Model(type: AbstractItemResponseDTO::class)),
                        new OA\Schema(
                            properties: [
                                new OA\Property(
                                    property: 'data',
                                    ref: new Model(type: ClientResponse::class)
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
            )
        ]
    )]
    #[IsGranted('ROLE_CLIENT')]
    public function get(
        #[CurrentUser] Client $client,
        ClientMapperInterface $clientMapper,
    ): ItemResponse {
        return new ItemResponse(data: $clientMapper->map($client), status: Response::HTTP_OK);
    }

    /**
     * @throws AccessDeniedHttpException
     */
    #[Route('/api/me/', methods: ['PATCH'], format: 'json')]
    #[OA\Patch(
        operationId: 'updateClient',
        summary: 'Update current client profile.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: UpdateClientRequest::class))
        ),
        tags: ['Client: Profile'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Profile updated successfully.',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: new Model(type: AbstractItemResponseDTO::class)),
                        new OA\Schema(
                            properties: [
                                new OA\Property(
                                    property: 'data',
                                    ref: new Model(type: ClientResponse::class)
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
                description: 'Forbidden - User is blocked or inactive',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 422,
                description: 'Validation failed',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            )
        ]
    )]
    #[IsGranted('ROLE_CLIENT')]
    public function update(
        #[CurrentUser] Client $client,
        #[MapRequestPayload] UpdateClientRequest $requestDto,
        ClientMapperInterface $mapper,
        ClientManager $manager,
    ): ItemResponse {
        $responseDto = $mapper->map($manager->update($client, $requestDto));

        return new ItemResponse(data: $responseDto, status: Response::HTTP_OK);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ConflictHttpException
     */
    #[Route('/api/me/', methods: ['DELETE'], format: 'json')]
    #[OA\Delete(
        operationId: 'deleteClient',
        summary: 'Soft delete current client account and clear sessions.',
        tags: ['Client: Profile'],
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
    #[IsGranted('ROLE_CLIENT')]
    public function delete(
        #[CurrentUser] Client $client,
        ClientManager $manager
    ): Response {
        $manager->softDelete($client);

        $this->container->get('security.token_storage')->setToken(null);

        $response = new Response(status: Response::HTTP_NO_CONTENT);
        $response->headers->clearCookie('access_token', '/', '.evogym.local');
        $response->headers->clearCookie('refresh_token', '/', '.evogym.local');

        return $response;
    }

    /**
     * @throws NoActiveMembershipException
     * @throws AccessDeniedHttpException
     */
    #[Route('/api/me/visit/', methods: ['POST'], format: 'json')]
    #[OA\Post(
        operationId: 'clientVisit',
        summary: 'Register a gym visit using active membership.',
        tags: ['Client: Profile'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Visit registered successfully.',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: new Model(type: AbstractItemResponseDTO::class)),
                        new OA\Schema(
                            properties: [
                                new OA\Property(
                                    property: 'data',
                                    ref: new Model(type: MembershipResponse::class)
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Bad Request - No active membership or session limit reached.',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthorized',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 403,
                description: 'Forbidden - User is blocked or inactive',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            )
        ]
    )]
    #[IsGranted('ROLE_CLIENT')]
    public function visit(
        #[CurrentUser] Client $client,
        MembershipMapperInterface $mapper,
        ClientManager $manager,
    ): ItemResponse {
        $responseDto = $mapper->map($manager->visit($client));

        return new ItemResponse(data: $responseDto, status: Response::HTTP_OK);
    }

    /**
     * @throws AccessDeniedHttpException
     */
    #[Route('/api/me/topup/', methods: ['POST'], format: 'json')]
    #[OA\Post(
        operationId: 'topUpBalance',
        summary: 'Create a payment intent to top up client balance.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: TopUpBalanceRequest::class))
        ),
        tags: ['Client: Profile'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Payment intent created successfully.',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: new Model(type: AbstractItemResponseDTO::class)),
                        new OA\Schema(
                            properties: [
                                new OA\Property(
                                    property: 'data',
                                    ref: new Model(type: PaymentResponse::class)
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
                description: 'Forbidden - User is blocked or inactive',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 422,
                description: 'Validation failed',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            )
        ]
    )]
    #[IsGranted('ROLE_CLIENT')]
    public function topUpBalance(
        #[CurrentUser] Client $client,
        #[MapRequestPayload] TopUpBalanceRequest $requestDto,
        PaymentMapperInterface $mapper,
        ClientManager $manager,
    ): ItemResponse {
        $responseDto = $mapper->map($manager->topUpBalance($client, $requestDto));

        return new ItemResponse(data: $responseDto, status: Response::HTTP_OK);
    }
}
