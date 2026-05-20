<?php

declare(strict_types=1);

namespace App\User\Exception;

use DomainException;
use Symfony\Component\HttpKernel\Attribute\WithHttpStatus;
use Throwable;

#[WithHttpStatus(409)]
final class UserAlreadyNotBlockedException extends DomainException
{
    public function __construct(
        string $message = 'User already not blocked',
        int $code = 0,
        ?Throwable $previous = null
    )
    {
        parent::__construct($message, $code, $previous);
    }
}
