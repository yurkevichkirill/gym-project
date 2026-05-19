<?php

declare(strict_types=1);

namespace App\Payment\Exception;

use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Throwable;

final class InvalidPaymentStatusException extends ConflictHttpException
{
    /**
     * @param array<string, string|string[]> $headers
     */
    public function __construct(string $message = 'Invalid payment status', ?Throwable $previous = null, int $code = 0, array $headers = [])
    {
        parent::__construct($message, $previous, $code, $headers);
    }
}
