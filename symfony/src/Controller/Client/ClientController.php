<?php

namespace App\Controller\Client;

use App\Client\DTO\ClientActivateRequest;
use App\Client\DTO\TopUpBalanceRequest;
use App\Client\DTO\UpdateClientRequest;
use App\Client\Entity\Client;
use App\Client\Mapper\ClientMapperInterface;
use App\Client\Service\ClientManager;
use App\Membership\Mapper\MembershipMapperInterface;
use App\Payment\Mapper\PaymentMapperInterface;
use App\Response\ItemResponse;
use App\Response\OkResponse;
use DateMalformedStringException;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ClientController extends AbstractController
{
    #[Route('/api/me/', methods: ['GET'], format: 'json')]
    #[OA\Tag(name: "Client: Client")]
    public function get(
        #[CurrentUser] Client $client,
        ClientMapperInterface $clientMapper,
    ): ItemResponse
    {
        $responseDto = $clientMapper->map($client);

        return new ItemResponse(
            data: $responseDto,
            status: Response::HTTP_OK,
        );
    }

    #[Route('/api/me/', methods: ['PUT', 'PATCH'], format: 'json')]
    #[OA\RequestBody(content: new Model(type: UpdateClientRequest::class))]
    #[OA\Tag(name: "Client: Client")]
    #[IsGranted('ROLE_CLIENT')]
    public function update(
        #[CurrentUser] Client $client,
        #[MapRequestPayload] UpdateClientRequest $requestDto,
        ClientMapperInterface $mapper,
        ClientManager $manager,
    ): ItemResponse
    {
        $responseDto = $mapper->map($manager->update($client, $requestDto));

        return new ItemResponse(
            data: $responseDto,
            status: Response::HTTP_OK,
        );
    }

    #[Route('/api/me/', methods: ['DELETE'], format: 'json')]
    #[IsGranted('ROLE_CLIENT')]
    #[OA\Tag(name: "Client: Client")]
    public function delete(
        #[CurrentUser] Client $client,
        ClientManager $manager
    ): Response
    {
        $manager->softDelete($client);
        $this->container->get('security.token_storage')->setToken(null);

        $response = new Response(status: Response::HTTP_NO_CONTENT);

        $response->headers->clearCookie(
            'access_token',
            '/',
            '.evogym.local',
        );

        $response->headers->clearCookie(
            'refresh_token',
            '/',
            '.evogym.local',
        );

        return $response;
    }

    #[Route('/api/me/visit/', methods: ['POST'], format: 'json')]
    #[OA\Tag(name: "Client: Client")]
    #[IsGranted('ROLE_CLIENT')]
    public function visit(
        #[CurrentUser] Client $client,
        MembershipMapperInterface $mapper,
        ClientManager $manager,
    ): ItemResponse
    {
        $responseDto = $mapper->map($manager->visit($client));

        return new ItemResponse(
            data: $responseDto,
            status: Response::HTTP_OK,
        );
    }

    #[Route('/api/me/topup/', methods: ['POST'], format: 'json')]
    #[OA\RequestBody(content: new Model(type: TopUpBalanceRequest::class))]
    #[OA\Tag(name: "Client: Client")]
    #[IsGranted('ROLE_CLIENT')]
    public function topUpBalance(
        #[CurrentUser] Client $client,
        #[MapRequestPayload] TopUpBalanceRequest $requestDto,
        PaymentMapperInterface $mapper,
        ClientManager $manager,
    ): ItemResponse
    {
        $responseDto = $mapper->map($manager->topUpBalance($client, $requestDto));

        return new ItemResponse(
            data: $responseDto,
            status: Response::HTTP_OK,
        );
    }

    #[Route('/api/me/activate/', methods: ['POST'], format: 'json')]
    #[OA\RequestBody(content: new Model(type: ClientActivateRequest::class))]
    #[OA\Tag(name: "Client: Client")]
    public function activate(
        #[MapRequestPayload] ClientActivateRequest $requestDto,
        ClientMapperInterface $mapper,
        ClientManager $manager,
    ): ItemResponse
    {
        $responseDto = $mapper->map($manager->activate($requestDto));

        return new ItemResponse(
            data: $responseDto,
            status: Response::HTTP_OK,
        );
    }
}
