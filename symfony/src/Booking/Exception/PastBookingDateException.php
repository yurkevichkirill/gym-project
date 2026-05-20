<?php

declare(strict_types=1);

namespace App\Booking\Exception;

use DomainException;
use Symfony\Component\HttpKernel\Attribute\WithHttpStatus;
use Throwable;

#[WithHttpStatus(400)]
final class PastBookingDateException extends DomainException
{
    public function __construct(
        string $message = 'Cannot book training in the past',
        int $code = 0,
        ?Throwable $previous = null
    )
    {
        parent::__construct($message, $code, $previous);
    }
}
