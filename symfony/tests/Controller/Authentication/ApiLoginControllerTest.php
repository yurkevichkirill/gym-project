<?php

declare(strict_types=1);

namespace App\Tests\Controller\Authentication;

use App\Controller\Authentication\ApiLoginController;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;

final class ApiLoginControllerTest extends TestCase
{
    public function testSetAuthCookiesUsesConfiguredDomain(): void
    {
        $controller = new ApiLoginController('.example.com');
        $response = new JsonResponse();

        $method = new ReflectionMethod($controller, 'setAuthCookies');
        $method->invoke($controller, $response, 'access-token', 'refresh-token');

        $cookies = [];
        foreach ($response->headers->getCookies() as $cookie) {
            $cookies[$cookie->getName()] = $cookie;
        }

        self::assertArrayHasKey('access_token', $cookies);
        self::assertArrayHasKey('refresh_token', $cookies);

        foreach ($cookies as $cookie) {
            self::assertSame('.example.com', $cookie->getDomain());
            self::assertSame('/', $cookie->getPath());
            self::assertTrue($cookie->isSecure());
            self::assertTrue($cookie->isHttpOnly());
            self::assertSame(Cookie::SAMESITE_LAX, $cookie->getSameSite());
        }
    }
}
