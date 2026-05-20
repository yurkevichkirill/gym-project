<?php

declare(strict_types=1);

namespace App\Booking\Exception;

use DomainException;
use Symfony\Component\HttpKernel\Attribute\WithHttpStatus;
use Throwable;

#[WithHttpStatus(409)]
final class TrainingWithoutBookingException extends DomainException
{
    public function __construct(
        string $message = 'Training has no booking.',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
