<?php

declare(strict_types=1);

namespace App\Infrastructure\ClickHouse;

use InvalidArgumentException;
use JsonException;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class ClickHouseClient
{
    public function __construct(
        private HttpClientInterface $http,
        private string $host,
        private string $database,
        private string $user,
        private string $password,
    ) {}

    /**
     * @param list<array<string, scalar|null>> $rows
     *
     * @throws JsonException
     * @throws TransportExceptionInterface
     */
    public function insert(string $table, array $rows): void
    {
        if ($rows === []) {
            return;
        }

        if (preg_match('/^[a-z][a-z0-9_]*$/D', $table) !== 1) {
            throw new InvalidArgumentException('Invalid ClickHouse table name');
        }

        $encodedRows = array_map(
            static fn (array $row): string => json_encode($row, JSON_THROW_ON_ERROR),
            $rows,
        );

        $response = $this->http->request('POST', $this->host, [
            'auth_basic' => [$this->user, $this->password],
            'query' => [
                'query' => "INSERT INTO $this->database.$table FORMAT JSONEachRow",
                'async_insert' => 1,
                'wait_for_async_insert' => 1,
            ],
            'body' => implode("\n", $encodedRows),
        ]);

        // Force the HTTP request to complete and throw for 4xx/5xx responses
        // before Messenger acknowledges the analytics message.
        $response->getContent();
    }
}
