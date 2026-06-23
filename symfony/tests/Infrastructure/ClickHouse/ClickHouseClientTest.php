<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\ClickHouse;

use App\Infrastructure\ClickHouse\ClickHouseClient;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class ClickHouseClientTest extends TestCase
{
    public function testInsertWaitsForSuccessfulClickHouseResponse(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response
            ->expects(self::once())
            ->method('getContent')
            ->willReturn('');

        $http = $this->createMock(HttpClientInterface::class);
        $http
            ->expects(self::once())
            ->method('request')
            ->with(
                'POST',
                'http://clickhouse:8123/',
                self::callback(static function (array $options): bool {
                    return $options['query'] === [
                        'query' => 'INSERT INTO analytics.booking_events FORMAT JSONEachRow',
                        'async_insert' => 1,
                        'wait_for_async_insert' => 1,
                    ]
                        && $options['body'] === '{"event_id":"event-1"}'
                        && $options['auth_basic'] === ['analytics', 'secret'];
                }),
            )
            ->willReturn($response);

        $client = new ClickHouseClient(
            $http,
            'http://clickhouse:8123/',
            'analytics',
            'analytics',
            'secret',
        );

        $client->insert('booking_events', [
            ['event_id' => 'event-1'],
        ]);
    }

    public function testInsertRejectsUnsafeTableNameBeforeRequest(): void
    {
        $http = $this->createMock(HttpClientInterface::class);
        $http->expects(self::never())->method('request');

        $client = new ClickHouseClient(
            $http,
            'http://clickhouse:8123/',
            'analytics',
            'analytics',
            'secret',
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid ClickHouse table name');

        $client->insert('booking_events; DROP TABLE users', [
            ['event_id' => 'event-1'],
        ]);
    }
}
