<?php

declare(strict_types=1);

namespace App\Tests\Controller\Authentication;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class CurrentUserFunctionalTest extends WebTestCase
{
    public function testUnauthenticatedRequestReturnsUnauthorized(): void
    {
        $browser = self::createClient();
        $browser->jsonRequest('GET', '/api/auth/me/');

        self::assertSame(
            Response::HTTP_UNAUTHORIZED,
            $browser->getResponse()->getStatusCode(),
            (string) $browser->getResponse()->getContent(),
        );
    }
}
