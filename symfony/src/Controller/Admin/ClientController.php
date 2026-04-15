<?php

namespace App\Controller\Admin;

use App\Admin\Entity\Admin;
use App\Client\DTO\AdminUpdateClientRequest;
use App\Client\DTO\CreateClientRequest;
use App\Client\Entity\Client;
use App\Client\Factory\GetClientFactory;
use App\Client\Mapper\ClientMapperInterface;
use App\Client\Query\ClientQuery;
use App\Client\Service\ClientManager;
use App\Membership\Mapper\MembershipMapperInterface;
use App\Response\OkResponse;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Psr\Cache\InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ClientController extends AbstractController
{
    #[Route('/api/clients/', name: 'app_api_clients', methods: ['GET'], format: 'json')]
    #[OA\Parameter(name: 'minAge', in: 'query', example: 18)]
    #[OA\Parameter(name: 'maxAge', in: 'query', example: 18)]
    #[OA\Parameter(name: 'minBalance', in: 'query', example: 0)]
    #[OA\Parameter(name: 'maxBalance', in: 'query', example: 0)]
    #[OA\Parameter(name: 'isDeleted', in: 'query', example: 'true')]
    #[OA\Parameter(name: 'sort', in: 'query', example: 'age:ASC')]
    #[OA\Parameter(name: 'page', in: 'query', example: 1)]
    #[OA\Parameter(name: 'limit', in: 'query', example: 20)]
    #[OA\Tag(name: "Admin: Client")]
    #[IsGranted('ROLE_ADMIN')]
    public function getAll(
        ClientMapperInterface $mapper,
        Request $request,
        ClientQuery $handler,
        GetClientFactory $factory,
    ): OkResponse
    {
        $queryDto = $factory->fromRequest($request);

        $clients = $handler->handle($queryDto);

        return new OkResponse(
            array_map(fn($client) => $mapper->map($client), $clients),
            $queryDto->page,
            $queryDto->limit,
            $handler->getTotal($queryDto->filter),
            $queryDto->sort,
            Response::HTTP_OK
        );
    }

    #[Route('/api/clients/{id}/', methods: ['GET'], format: 'json')]
    #[OA\Tag(name: "Admin: Client")]
    #[IsGranted('ROLE_ADMIN')]
    public function get(
        Client $client,
        ClientMapperInterface $mapper,
    ): OkResponse
    {
        return new OkResponse(
            data: $mapper->map($client),
            status: Response::HTTP_OK,
        );
    }

    #[Route('/api/clients/', methods: ['POST'], format: 'json')]
    #[OA\RequestBody(content: new Model(type: CreateClientRequest::class))]
    #[OA\Tag(name: "Admin: Client")]
    #[IsGranted('ROLE_ADMIN')]
    public function create(
        #[MapRequestPayload] CreateClientRequest $requestDto,
        ClientMapperInterface $mapper,
        ClientManager $manager,
    ): OkResponse
    {
        $responseDto = $mapper->map($manager->create($requestDto));

        return new OkResponse(
            data: $responseDto,
            status: Response::HTTP_OK,
        );
    }

    #[Route('/api/clients/{id}/', methods: ['PUT', 'PATCH'], format: 'json')]
    #[OA\RequestBody(content: new Model(type: AdminUpdateClientRequest::class))]
    #[OA\Tag(name: "Admin: Client")]
    #[IsGranted('ROLE_ADMIN')]
    public function update(
        Client $client,
        #[MapRequestPayload] AdminUpdateClientRequest $requestDto,
        ClientMapperInterface $mapper,
        ClientManager $manager,
    ): OkResponse
    {
        $responseDto = $mapper->map($manager->updateByAdmin($client, $requestDto));

        return new OkResponse(
            data: $responseDto,
            status: Response::HTTP_OK,
        );
    }

    /**
     * @throws OptimisticLockException
     * @throws NotFoundExceptionInterface
     * @throws ORMException
     * @throws ContainerExceptionInterface
     */
    #[Route('/api/clients/{id}/', methods: ['DELETE'], format: 'json')]
    #[OA\Tag(name: "Admin: Client")]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(
        #[CurrentUser] Admin $admin,
        Client $client,
        ClientManager $manager,
    ): Response
    {
        $manager->softDelete($client, $admin);
        $this->container->get('security.token_storage')->setToken(null);
        //clean cookies in frontend

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    #[Route('/api/clients/{id}/restore/', methods: ['POST'], format: 'json')]
    #[OA\Tag(name: "Admin: Client")]
    #[IsGranted('ROLE_ADMIN')]
    public function restore(
        Client $client,
        ClientManager $manager,
        ClientMapperInterface $mapper,
    ): OkResponse
    {
        $responseDto = $mapper->map($manager->restore($client));

        return new OkResponse(
            data: $responseDto,
            status: Response::HTTP_OK,
        );
    }

    #[Route('/api/clients/{id}/block/', methods: ['POST'], format: 'json')]
    #[OA\Tag(name: "Admin: Client")]
    #[IsGranted('ROLE_ADMIN')]
    public function block(
        Client $client,
        #[CurrentUser] Admin $admin,
        ClientMapperInterface $mapper,
        ClientManager $manager,
    ): OkResponse
    {
        $responseDto = $mapper->map($manager->block($admin, $client));

        return new OkResponse(
            data: $responseDto,
            status: Response::HTTP_OK,
        );
    }

    #[Route('/api/clients/{id}/unblock/', methods: ['POST'], format: 'json')]
    #[OA\Tag(name: "Admin: Client")]
    #[IsGranted('ROLE_ADMIN')]
    public function unblock(
        Client $client,
        ClientMapperInterface $mapper,
        ClientManager $manager,
    ): OkResponse
    {
        $responseDto = $mapper->map($manager->unblock($client));

        return new OkResponse(
            data: $responseDto,
            status: Response::HTTP_OK,
        );
    }

    #[Route('/api/clients/{id}/visit/', methods: ['POST'], format: 'json')]
    #[OA\Tag(name: "Admin: Client")]
    #[IsGranted('ROLE_ADMIN')]
    public function visit(
        Client $client,
        MembershipMapperInterface $mapper,
        ClientManager $manager,
    ): OkResponse
    {
        $responseDto = $mapper->map($manager->visit($client));

        return new OkResponse(
            data: $responseDto,
            status: Response::HTTP_OK,
        );
    }
}
