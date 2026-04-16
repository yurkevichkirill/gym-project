<?php

declare(strict_types=1);

namespace App\ImportJob\Enum;

enum ImportStatusEnum: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case DONE = 'done';
    case FAILED = 'failed';
}
