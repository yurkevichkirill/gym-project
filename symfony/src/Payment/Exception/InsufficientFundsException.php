<?php

declare(strict_types=1);

namespace App\Payment\Exception;

use DomainException;
use Symfony\Component\HttpKernel\Attribute\WithHttpStatus;
use Throwable;

#[WithHttpStatus(402)]
final class InsufficientFundsException extends DomainException
{
    public function __construct(
        string $message = 'Insufficient funds for buying',
        ?Throwable $previous = null,
        int $code = 0
    )
    {
        parent::__construct($message, $code, $previous);
    }
}
