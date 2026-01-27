<?php

declare(strict_types=1);

namespace App\Enum;

enum TrainingStatusEnum: string
{
    case SCHEDULED = 'scheduled';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
}
