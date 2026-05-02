<?php

declare(strict_types=1);

namespace App\ImportJob\Message;

use App\ImportJob\DTO\CreateClientImport;

final readonly class ImportMessage
{
    public function __construct(
        public CreateClientImport $dto,
        public int $jobId,
        public int $rowIndex,
    ) {}
}
