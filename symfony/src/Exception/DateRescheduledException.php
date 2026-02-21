<?php

declare(strict_types=1);

namespace App\Exception;

use Exception;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Throwable;

class DateRescheduledException  extends ConflictHttpException
{
    public function __construct(string $message = 'This date cannot be selected', ?\Throwable $previous = null, int $code = 0, array $headers = [])
    {
        parent::__construct($message, $previous, $code, $headers);
    }
}
