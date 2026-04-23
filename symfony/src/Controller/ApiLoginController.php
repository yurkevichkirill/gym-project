<?php

namespace App\Controller;

use App\RefreshToken\Service\RefreshTokenManager;
use App\User\DTO\LoginUserRequest;
use App\User\Service\UserManager;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Nelmio\ApiDocBundle\Attribute\Model;
use Random\RandomException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;

final class ApiLoginController extends AbstractController
{
    #[Route('/api/login/', name: 'app_api_login', methods: ['POST'])]
    #[OA\Tag(name: "Authorization")]
    #[OA\RequestBody(content: new Model(type: LoginUserRequest::class))]
    public function login(
        #[MapRequestPayload] LoginUserRequest $dto,
        UserManager $userManager,
        RefreshTokenManager $refreshTokenManager,
    ): JsonResponse
    {
        $user = $userManager->login($dto);

        $accessToken = $refreshTokenManager->generateAccessToken($user);
        $refreshToken = $refreshTokenManager->generateRefreshToken();

        $refreshTokenManager->create($refreshToken, $user);

        $response = $this->json([
            'data' => ['user' => $user->getUserIdentifier()]
        ]);

        $accessTokenCookie = Cookie::create(
            'access_token',
            $accessToken,
            time() + 3000,
            '/',
            '.evogym.local',
            true,
            true,
            false,
            'none'
        );

        $refreshTokenCookie = Cookie::create(
            'refresh_token',
            $refreshToken,
            time() + 604800,
            '/',
            '.evogym.local',
            true,
            true,
            false,
            'none',
        );

        $response->headers->setCookie($accessTokenCookie);
        $response->headers->setCookie($refreshTokenCookie);

        return $response;
    }

    /**
     * @throws OptimisticLockException
     * @throws RandomException
     * @throws ORMException
     */
    #[Route('/api/refresh/', methods: ['POST'])]
    #[OA\Tag(name: "Authorization")]
    public function refresh(
        Request $request,
        RefreshTokenManager $manager,
    ): JsonResponse
    {
        $refreshToken = $request->cookies->get('refresh_token');
        [$newAccessToken, $newRefreshToken] = $manager->refresh($refreshToken);

        $response = new JsonResponse();

        $response->headers->setCookie(
            Cookie::create(
                'access_token',
                $newAccessToken,
                time()+900,
                '/',
                '.evogym.local',
                true,
                true,
                false,
                'none'
            )
        );

        $response->headers->setCookie(
            Cookie::create(
                'refresh_token',
                $newRefreshToken,
                time()+604800,
                '/',
                '.evogym.local',
                true,
                true,
                false,
                'none'
            )
        );

        return $response;
    }

    #[Route("/api/logout/", methods: ['POST'])]
    #[OA\Tag(name: "Authorization")]
    public function logout(): JsonResponse
    {
        $response = new JsonResponse();

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
}
