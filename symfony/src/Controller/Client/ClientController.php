<?php

namespace App\Controller\Client;

use App\Client\DTO\UpdateClientRequest;
use App\Client\Entity\Client;
use App\Client\Mapper\ClientMapperInterface;
use App\Client\Service\ClientManager;
use App\Response\OkResponse;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
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
    #[IsGranted('ROLE_CLIENT')]
    #[OA\Tag(name: "Client: Client")]
    #[IsGranted('ROLE_CLIENT')]
    public function get(
        #[CurrentUser] Client $client,
        ClientMapperInterface $mapper,
    ): OkResponse
    {
        $responseDto = $mapper->map($client);

        return new OkResponse(
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
    ): OkResponse
    {
        $responseDto = $mapper->map($manager->update($client, $requestDto));

        return new OkResponse(
            data: $responseDto,
            status: Response::HTTP_OK,
        );
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
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

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
