<?php

declare(strict_types=1);

namespace App\Controller\Authentication;

use App\RefreshToken\Service\RefreshTokenManager;
use App\Response\DTO\ErrorResponseDTO;
use App\User\DTO\LoginUserRequest;
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
    #[OA\Tag(name: "Authentication")]
    #[OA\Post(
        operationId: 'authLogin',
        summary: 'Authenticate user and set JWT cookies.',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login success. Tokens are set in HttpOnly cookies.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', properties: [
                            new OA\Property(property: 'user', type: 'string', example: 'user@example.com')
                        ])
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
                description: 'User account is deleted/blocked',
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
        #[MapRequestPayload] LoginUserRequest $dto,
        UserManager $userManager,
        RefreshTokenManager $refreshTokenManager,
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
}
