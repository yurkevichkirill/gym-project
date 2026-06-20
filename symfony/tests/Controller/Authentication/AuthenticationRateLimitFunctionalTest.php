<?php

declare(strict_types=1);

namespace App\Tests\Controller\Authentication;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class AuthenticationRateLimitFunctionalTest extends WebTestCase
{
    /**
     * @param non-empty-string $path
     * @param non-empty-string $ip
     */
    #[DataProvider('targetPostEndpointProvider')]
    public function testTargetPostEndpointsAreRateLimitedByClientIp(string $path, string $ip): void
    {
        $client = $this->createIsolatedClient();

        $this->requestJson($client, 'POST', $path, $ip);
        self::assertNotSame(Response::HTTP_TOO_MANY_REQUESTS, $client->getResponse()->getStatusCode());

        $this->requestJson($client, 'POST', $path, $ip);
        $this->assertTooManyRequestsResponse($client);

        $this->requestJson($client, 'POST', $path, $ip . '.200');
        self::assertNotSame(Response::HTTP_TOO_MANY_REQUESTS, $client->getResponse()->getStatusCode());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function targetPostEndpointProvider(): iterable
    {
        yield 'login' => ['/api/login/', '198.51.100.10'];
        yield 'refresh' => ['/api/refresh/', '198.51.100.20'];
        yield 'activation' => ['/api/clients/activate/', '198.51.100.30'];
        yield 'registration' => ['/api/client/registration/', '198.51.100.40'];
    }

    public function testGetRequestToTargetPathDoesNotConsumePostIpLimiter(): void
    {
        $client = $this->createIsolatedClient();
        $ip = '198.51.100.50';

        $this->requestJson($client, 'GET', '/api/login/', $ip);
        self::assertNotSame(Response::HTTP_TOO_MANY_REQUESTS, $client->getResponse()->getStatusCode());

        $this->requestJson($client, 'POST', '/api/login/', $ip);
        self::assertNotSame(Response::HTTP_TOO_MANY_REQUESTS, $client->getResponse()->getStatusCode());

        $this->requestJson($client, 'POST', '/api/login/', $ip);
        $this->assertTooManyRequestsResponse($client);
    }

    public function testUnrelatedPostRouteDoesNotConsumeAuthenticationLimiter(): void
    {
        $client = $this->createIsolatedClient();
        $ip = '198.51.100.60';

        $this->requestJson($client, 'POST', '/api/not-authentication/', $ip);
        self::assertNotSame(Response::HTTP_TOO_MANY_REQUESTS, $client->getResponse()->getStatusCode());

        $this->requestJson($client, 'POST', '/api/login/', $ip);
        self::assertNotSame(Response::HTTP_TOO_MANY_REQUESTS, $client->getResponse()->getStatusCode());

        $this->requestJson($client, 'POST', '/api/login/', $ip);
        $this->assertTooManyRequestsResponse($client);
    }


    private function createIsolatedClient(): KernelBrowser
    {
        $client = self::createClient();
        $pool = self::getContainer()->get('cache.auth_rate_limiter');
        self::assertTrue($pool->clear());

        return $client;
    }

    /**
     * @param non-empty-string $method
     * @param non-empty-string $path
     * @param non-empty-string $ip
     */
    private function requestJson(KernelBrowser $client, string $method, string $path, string $ip): void
    {
        $client->request(
            $method,
            $path,
            server: [
                'REMOTE_ADDR' => $ip,
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            content: '{}',
        );
    }

    private function assertTooManyRequestsResponse(KernelBrowser $client): void
    {
        $response = $client->getResponse();

        self::assertSame(Response::HTTP_TOO_MANY_REQUESTS, $response->getStatusCode(), (string) $response->getContent());
        self::assertStringStartsWith('application/json', (string) $response->headers->get('Content-Type'));
        $content = $response->getContent();
        self::assertIsString($content);
        self::assertSame(['message' => 'Too many requests. Try again later.'], json_decode($content, true));

        $retryAfter = $response->headers->get('Retry-After');
        self::assertNotNull($retryAfter);
        self::assertMatchesRegularExpression('/^\d+$/', $retryAfter);
        self::assertGreaterThanOrEqual(1, (int) $retryAfter);
    }
}
