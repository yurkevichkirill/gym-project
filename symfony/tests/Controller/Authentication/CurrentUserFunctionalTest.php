<?php

declare(strict_types=1);

namespace App\Tests\Controller\Authentication;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CurrentUserFunctionalTest extends WebTestCase
{
    public function testJwtManagerServiceIsAvailable(): void
    {
        self::createClient();

        $jwtManager = self::getContainer()->get('lexik_jwt_authentication.jwt_manager');

        self::assertInstanceOf(JWTTokenManagerInterface::class, $jwtManager);
    }
}
