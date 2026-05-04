<?php

declare(strict_types=1);

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Throwable;

final class InvalidPaymentStatusException extends ConflictHttpException
{
    public function __construct(string $message = 'Invalid payment status', ?Throwable $previous = null, int $code = 0, array $headers = [])
    {
        parent::__construct($message, $previous, $code, $headers);
    }
}
