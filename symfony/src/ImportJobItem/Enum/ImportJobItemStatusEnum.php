<?php

declare(strict_types=1);

namespace App\ImportJobItem\Enum;

enum ImportJobItemStatusEnum: string
{
    case PROCESSING = 'processing';
    case SUCCEEDED = 'succeeded';
    case FAILED = 'failed';
    case SKIPPED = 'skipped';
}
