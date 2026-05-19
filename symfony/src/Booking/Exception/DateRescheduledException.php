<?php

declare(strict_types=1);

namespace App\Booking\Exception;

use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Throwable;

final class DateRescheduledException extends ConflictHttpException
{
    /**
     * @param array<string, string|string[]> $headers
     */
    public function __construct(string $message = 'This date cannot be selected', ?Throwable $previous = null, int $code = 0, array $headers = [])
    {
        parent::__construct($message, $previous, $code, $headers);
    }
}
