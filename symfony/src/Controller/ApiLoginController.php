<?php

namespace App\Controller;

use App\Client\Entity\Client;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use OpenApi\Attributes as OA;

final class ApiLoginController extends AbstractController
{
    #[Route('/api/login', name: 'app_api_login', methods: ['POST'])]
    #[OA\Tag(name: "Login")]
    #[OA\RequestBody(content: new Model(type: Client::class, groups: ['login']))]
    public function login(#[CurrentUser] ?Client $client, JWTTokenManagerInterface $jwtManager): JsonResponse
    {
        if(null === $client) {
            return $this->json([
                'message' => 'missing credentials',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $token = $jwtManager->create($client);

        return $this->json([
            'user' => $client->getUserIdentifier(),
            'token' => $token,
        ]);
    }
}
