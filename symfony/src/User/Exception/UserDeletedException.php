<?php

declare(strict_types=1);

namespace App\User\Exception;

use DomainException;
use Symfony\Component\HttpKernel\Attribute\WithHttpStatus;
use Throwable;

#[WithHttpStatus(403)]
final class UserDeletedException extends DomainException
{
    public function __construct(
        string $message = 'User is deleted',
        int $code = 0,
        ?Throwable $previous = null
    )
    {
        parent::__construct($message, $code, $previous);
    }
}
