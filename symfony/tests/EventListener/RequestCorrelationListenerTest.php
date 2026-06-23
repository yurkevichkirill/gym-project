<?php

declare(strict_types=1);

namespace App\Tests\EventListener;

use App\EventListener\RequestCorrelationListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class RequestCorrelationListenerTest extends TestCase
{
    public function testIncomingIdentifiersAreAddedToRequestAndResponse(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/api/trainers/');
        $request->headers->set('X-Request-Id', 'request-123');
        $request->headers->set('X-Correlation-Id', 'correlation-456');
        $listener = new RequestCorrelationListener();

        $listener->onKernelRequest(new RequestEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
        ));

        self::assertSame('request-123', $request->attributes->get('_request_id'));
        self::assertSame('correlation-456', $request->attributes->get('_correlation_id'));

        $response = new Response();
        $listener->onKernelResponse(new ResponseEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $response,
        ));

        self::assertSame('request-123', $response->headers->get('X-Request-Id'));
        self::assertSame('correlation-456', $response->headers->get('X-Correlation-Id'));
    }

    public function testInvalidIdentifierIsReplacedWithGeneratedRequestId(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/api/trainers/');
        $request->headers->set('X-Request-Id', 'invalid value with spaces');
        $listener = new RequestCorrelationListener();

        $listener->onKernelRequest(new RequestEvent(
            $kernel,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
        ));

        $requestId = $request->attributes->getString('_request_id');

        self::assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/D',
            $requestId,
        );
        self::assertSame($requestId, $request->attributes->get('_correlation_id'));
    }
}
