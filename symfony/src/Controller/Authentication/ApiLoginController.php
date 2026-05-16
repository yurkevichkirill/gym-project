<?php

declare(strict_types=1);

namespace App\Controller\Authentication;

use App\Client\DTO\ClientActivateRequest;
use App\Client\DTO\ClientResponse;
use App\Client\Mapper\ClientMapperInterface;
use App\Client\Service\ClientManager;
use App\RefreshToken\Service\RefreshTokenManager;
use App\Response\DTO\AbstractItemResponseDTO;
use App\Response\DTO\ErrorResponseDTO;
use App\Response\ItemResponse;
use App\User\DTO\LoginUserRequestDTO;
use App\User\Service\UserManager;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Random\RandomException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class ApiLoginController extends AbstractController
{
    /**
     * @throws OptimisticLockException
     * @throws RandomException
     * @throws ORMException
     * @throws UnauthorizedHttpException
     * @throws AccessDeniedHttpException
     */
    #[Route('/api/login/', name: 'app_api_login', methods: ['POST'])]
    #[OA\Post(
        operationId: 'authLogin',
        summary: 'Authenticate user and set JWT cookies.',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: new Model(type: LoginUserRequestDTO::class))
        ),
        tags: ['Authentication'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login success. Tokens are set in HttpOnly cookies.',
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: new Model(type: AbstractItemResponseDTO::class)),
                        new OA\Schema(
                            properties: [
                                new OA\Property(
                                    property: 'data',
                                    properties: [
                                        new OA\Property(property: 'user', type: 'string', example: 'user@example.com')
                                    ]
                                )
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Invalid credentials',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 403,
                description: 'User account is deleted or blocked',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 422,
                description: 'Validation failed (invalid JSON or DTO constraints)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
        ]
    )]
    public function login(
        #[MapRequestPayload] LoginUserRequestDTO $dto,
        UserManager                              $userManager,
        RefreshTokenManager                      $refreshTokenManager,
    ): JsonResponse {
        $user = $userManager->login($dto);

        $accessToken = $refreshTokenManager->generateAccessToken($user);
        $refreshToken = $refreshTokenManager->generateRefreshToken();

        $refreshTokenManager->create($refreshToken, $user);

        $response = $this->json([
            'data' => ['user' => $user->getUserIdentifier()]
        ]);

        $this->setAuthCookies($response, $accessToken, $refreshToken);

        return $response;
    }

    /**
     * @throws OptimisticLockException
     * @throws RandomException
     * @throws ORMException
     * @throws BadRequestException
     * @throws UnauthorizedHttpException
     * @throws AccessDeniedHttpException
     */
    #[Route('/api/refresh/', methods: ['POST'])]
    #[OA\Tag(name: "Authentication")]
    #[OA\Post(
        operationId: 'authRefresh',
        summary: 'Refresh access token using refresh_token cookie.',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Tokens refreshed. New cookies are set.'
            ),
            new OA\Response(
                response: 401,
                description: 'Invalid, missing or expired refresh token.',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 403,
                description: 'Access denied. User might be deleted or blocked.',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 400,
                description: 'Bad request - invalid data provided.',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            )
        ]
    )]
    public function refresh(
        Request $request,
        RefreshTokenManager $manager,
    ): JsonResponse {
        $refreshToken = $request->cookies->get('refresh_token');
        [$newAccessToken, $newRefreshToken] = $manager->refresh($refreshToken);

        $response = new JsonResponse();
        $this->setAuthCookies($response, $newAccessToken, $newRefreshToken);

        return $response;
    }

    #[Route("/api/logout/", methods: ['POST'])]
    #[OA\Tag(name: "Authentication")]
    #[OA\Post(
        operationId: 'authLogout',
        summary: 'Clear auth cookies.',
        responses: [
            new OA\Response(response: 200, description: 'Logged out')
        ]
    )]
    public function logout(): JsonResponse
    {
        $response = new JsonResponse();

        $response->headers->clearCookie('access_token', '/', '.evogym.local');
        $response->headers->clearCookie('refresh_token', '/', '.evogym.local');

        return $response;
    }

    private function setAuthCookies(JsonResponse $response, string $accessToken, string $refreshToken): void
    {
        $response->headers->setCookie(Cookie::create(
            'access_token', $accessToken, time() + 3600, '/', '.evogym.local', true, true, false, 'none'
        ));

        $response->headers->setCookie(Cookie::create(
            'refresh_token', $refreshToken, time() + 604800, '/', '.evogym.local', true, true, false, 'none'
        ));
    }

    #[Route('/api/clients/activate/', methods: ['POST'], format: 'json')]
    #[OA\Post(
        operationId: 'activateClient',
        summary: 'Activate client account using token and set password.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: new Model(type: ClientActivateRequest::class))
        ),
        tags: ['Authentication'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Account activated successfully.',
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
                response: 403,
                description: 'Forbidden - Account is blocked',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 404,
                description: 'Not Found - Invalid activation token',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 409,
                description: 'Conflict - Account is already activated',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
            ),
            new OA\Response(
                response: 422,
                description: 'Validation failed (e.g. weak password)',
                content: new OA\JsonContent(ref: new Model(type: ErrorResponseDTO::class))
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
