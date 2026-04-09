<?php

namespace App\Controller;

use App\Client\DTO\CreateClientRequest;
use App\Client\Mapper\ClientMapperInterface;
use App\Client\Service\ClientManager;
use App\Response\OkResponse;
use Nelmio\ApiDocBundle\Attribute\Model;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

final class RegistrationController extends AbstractController
{
    #[Route('/api/client/registration/', methods: ['POST'])]
    #[OA\Tag(name: "Registration")]
    #[OA\RequestBody(content: new Model(type: CreateClientRequest::class))]
    public function register(
        #[MapRequestPayload] CreateClientRequest $requestDto,
        ClientMapperInterface $mapper,
        ClientManager $manager,
    ): OkResponse
    {
        $responseDto = $mapper->map($manager->create($requestDto));

        return new OkResponse(
            data: $responseDto,
            status: 201,
        );
    }
}
