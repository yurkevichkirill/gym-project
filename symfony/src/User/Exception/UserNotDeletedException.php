<?php

declare(strict_types=1);

namespace App\User\Exception;

use DomainException;
use Symfony\Component\HttpKernel\Attribute\WithHttpStatus;
use Throwable;

#[WithHttpStatus(409)]
final class UserNotDeletedException extends DomainException
{
    public function __construct(
        string $message = 'User is not deleted',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
