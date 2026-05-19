<?php

declare(strict_types=1);

namespace App\Infrastructure\ClickHouse;

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
     * @throws TransportExceptionInterface
     */
    public function insert(string $table, array $rows): void
    {
        $body = implode("\n", array_map('json_encode', $rows));

        $this->http->request('POST', $this->host, [
            'auth_basic' => [$this->user, $this->password],
            'query' => [
                'query' => "INSERT INTO $this->database.$table FORMAT JSONEachRow",
            ],
            'body' => $body,
        ]);
    }
}
