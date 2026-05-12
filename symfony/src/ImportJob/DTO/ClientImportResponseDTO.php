<?php

declare(strict_types=1);

namespace App\ImportJob\DTO;

final readonly class ClientImportResponseDTO
{
    public function __construct(
        public string $status,
        public int $count,
        public int $jobId,
    ) {}
}
