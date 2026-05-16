<?php

declare(strict_types=1);

namespace App\Booking\Exception;

use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class DateTimeAlreadyTakenException extends ConflictHttpException
{
    public function __construct(string $message = 'This time is already taken"', ?\Throwable $previous = null, int $code = 0, array $headers = [])
    {
        parent::__construct($message, $previous, $code, $headers);
    }
}
