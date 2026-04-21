<?php

declare(strict_types=1);

namespace App\Infrastructure\ClickHouse\Buffer;

use App\Infrastructure\ClickHouse\ClickHouseClient;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

final class ClickHouseBuffer
{
    private array $buffer = [];
    private int $batchSize = 100;
    private int $lastFlush;
    private int $flushInterval = 5;

    public function __construct(
        private readonly ClickHouseClient $client,
    ) {
        $this->lastFlush = time();
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function add(string $table, array $row): void
    {
        $this->buffer[$table][] = $row;

        if (count($this->buffer[$table]) >= $this->batchSize) {
            $this->flush($table);
        }

        if (time() - $this->lastFlush >= $this->flushInterval) {
            $this->flushAll();
            $this->lastFlush = time();
        }
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function flush(string $table): void
    {
        if (empty($this->buffer[$table])) {
            return;
        }

        $this->client->insert($table, $this->buffer[$table]);

        $this->buffer[$table] = [];
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function flushAll(): void
    {
        foreach (array_keys($this->buffer) as $table) {
            $this->flush($table);
        }
    }
}
