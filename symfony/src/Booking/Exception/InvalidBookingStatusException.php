<?php

declare(strict_types=1);

namespace App\Booking\Exception;

use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class InvalidBookingStatusException extends ConflictHttpException
{
    /**
     * @param array<string, string|string[]> $headers
     */
    public function __construct(string $message = 'Invalid booking status', ?\Throwable $previous = null, int $code = 0, array $headers = [])
    {
        parent::__construct($message, $previous, $code, $headers);
    }
}
