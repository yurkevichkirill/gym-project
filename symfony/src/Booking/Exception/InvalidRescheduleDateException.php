<?php

declare(strict_types=1);

namespace App\Booking\Exception;

use DomainException;
use Symfony\Component\HttpKernel\Attribute\WithHttpStatus;
use Throwable;

#[WithHttpStatus(409)]
final class InvalidRescheduleDateException extends DomainException
{
    public function __construct(
        string $message = 'This date cannot be selected',
        ?Throwable $previous = null,
        int $code = 0
    )
    {
        parent::__construct($message, $code, $previous);
    }
}
