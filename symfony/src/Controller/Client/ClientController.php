<?php

namespace App\Controller\Client;

use App\Client\DTO\ClientActivateRequest;
use App\Client\DTO\ClientResponse;
use App\Client\DTO\TopUpBalanceRequest;
use App\Client\DTO\UpdateClientRequest;
use App\Client\Entity\Client;
use App\Client\Mapper\ClientMapperInterface;
use App\Client\Service\ClientManager;
use App\Membership\DTO\MembershipResponse;
use App\Membership\Mapper\MembershipMapperInterface;
use App\Payment\DTO\PaymentResponse;
use App\Payment\Mapper\PaymentMapperInterface;
use App\Response\ItemResponse;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ClientController extends AbstractController
{
    #[Route('/api/me/', methods: ['GET'], format: 'json')]
    #[OA\Get(
        operationId: 'getCurrentClient',
        summary: 'Get profile information of the current client.',
        tags: ['Client: Client'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Client profile data.',
                content: new OA\JsonContent(ref: new Model(type: ClientResponse::class))
            ),
            new OA\Response(response: 401, description: 'Unauthorized')
        ]
    )]
    #[IsGranted('ROLE_CLIENT')]
    public function get(
        #[CurrentUser] Client $client,
        ClientMapperInterface $clientMapper,
    ): ItemResponse {
        return new ItemResponse(data: $clientMapper->map($client), status: Response::HTTP_OK);
    }

    #[Route('/api/me/', methods: ['PUT', 'PATCH'], format: 'json')]
    #[OA\Put(
        operationId: 'updateClient',
        summary: 'Update current client profile.',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: new Model(type: UpdateClientRequest::class))
        ),
        tags: ['Client: Client'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Profile updated successfully.',
                content: new OA\JsonContent(ref: new Model(type: ClientResponse::class))
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 403, description: 'Forbidden - User is blocked or inactive'),
            new OA\Response(
                response: 422,
                description: 'Validation failed',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Validation failed'),
                        new OA\Property(property: 'request_id', type: 'string', nullable: true)
                    ]
                )
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
     */
    #[Route('/api/me/', methods: ['DELETE'], format: 'json')]
    #[OA\Delete(
        operationId: 'deleteClient',
        summary: 'Soft delete current client account and clear sessions.',
        tags: ['Client: Client'],
        responses: [
            new OA\Response(response: 204, description: 'Account deleted successfully.'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(
                response: 409,
                description: 'Conflict - Account already deleted',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Client already deleted'),
                        new OA\Property(property: 'request_id', type: 'string', nullable: true)
                    ]
                )
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

    #[Route('/api/me/visit/', methods: ['POST'], format: 'json')]
    #[OA\Post(
        operationId: 'clientVisit',
        summary: 'Register a gym visit using active membership.',
        tags: ['Client: Client'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Visit registered successfully.',
                content: new OA\JsonContent(ref: new Model(type: MembershipResponse::class))
            ),
            new OA\Response(
                response: 400,
                description: 'Bad Request - No active membership or session limit reached.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Client has no active membership'),
                        new OA\Property(property: 'request_id', type: 'string', nullable: true)
                    ]
                )
            ),
            new OA\Response(response: 403, description: 'Forbidden - User is blocked or inactive'),
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

    #[Route('/api/me/topup/', methods: ['POST'], format: 'json')]
    #[OA\Post(
        operationId: 'topUpBalance',
        summary: 'Create a payment intent to top up client balance.',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: new Model(type: TopUpBalanceRequest::class))
        ),
        tags: ['Client: Client'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Payment intent created.',
                content: new OA\JsonContent(ref: new Model(type: PaymentResponse::class))
            ),
            new OA\Response(response: 403, description: 'Forbidden - User is blocked or inactive'),
            new OA\Response(
                response: 422,
                description: 'Validation failed',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Validation failed')
                    ]
                )
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

    #[Route('/api/me/activate/', methods: ['POST'], format: 'json')]
    #[OA\Post(
        operationId: 'activateClient',
        summary: 'Activate client account using token and set password.',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: new Model(type: ClientActivateRequest::class))
        ),
        tags: ['Client: Client'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Account activated successfully.',
                content: new OA\JsonContent(ref: new Model(type: ClientResponse::class))
            ),
            new OA\Response(
                response: 404,
                description: 'Not Found - Invalid activation token.'
            ),
            new OA\Response(
                response: 409,
                description: 'Conflict - Account already activated',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Account is already activated.')
                    ]
                )
            )
        ]
    )]
    public function activate(
        #[MapRequestPayload] ClientActivateRequest $requestDto,
        ClientMapperInterface $mapper,
        ClientManager $manager,
    ): ItemResponse {
        $responseDto = $mapper->map($manager->activate($requestDto));

        return new ItemResponse(data: $responseDto, status: Response::HTTP_OK);
    }
}
