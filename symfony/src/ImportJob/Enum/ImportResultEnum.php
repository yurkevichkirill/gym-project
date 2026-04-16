<?php

declare(strict_types=1);

namespace App\ImportJob\Enum;

enum ImportResultEnum: string
{
    case CREATED = 'created';
    case SKIPPED = 'skipped';
}
