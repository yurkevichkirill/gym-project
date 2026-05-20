<?php

declare(strict_types=1);

namespace App\User\Exception;

use DomainException;
use Symfony\Component\HttpKernel\Attribute\WithHttpStatus;
use Throwable;

#[WithHttpStatus(409)]
final class UserAlreadyActiveException extends DomainException
{
    public function __construct(
        string $message = 'User is already active',
        int $code = 0,
        ?Throwable $previous = null
    )
    {
        parent::__construct($message, $code, $previous);
    }
}
