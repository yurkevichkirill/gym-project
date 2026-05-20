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
     * @param list<array<string, scalar|null>> $rows
     */
    public function insert(string $table, array $rows): void
    {
        $encodedRows = array_map(
            static function (array $row): string {
                $encoded = json_encode($row);

                return $encoded === false ? '{}' : $encoded;
            },
            $rows
        );
        $body = implode("\n", $encodedRows);

        $this->http->request('POST', $this->host, [
            'auth_basic' => [$this->user, $this->password],
            'query' => [
                'query' => "INSERT INTO $this->database.$table FORMAT JSONEachRow",
            ],
            'body' => $body,
        ]);
    }
}
