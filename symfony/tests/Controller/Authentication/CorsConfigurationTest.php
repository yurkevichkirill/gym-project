<?php

declare(strict_types=1);

namespace App\Tests\Controller\Authentication;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class CorsConfigurationTest extends WebTestCase
{
    public function testPreflightAllowsConfiguredOriginWithCredentials(): void
    {
        $browser = self::createClient();
        $browser->request('OPTIONS', '/api/login/', server: [
            'HTTP_ORIGIN' => 'https://evogym.local',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
        ]);

        $response = $browser->getResponse();

        self::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        self::assertSame(
            'https://evogym.local',
            $response->headers->get('Access-Control-Allow-Origin'),
        );
        self::assertSame('true', $response->headers->get('Access-Control-Allow-Credentials'));
    }
}
