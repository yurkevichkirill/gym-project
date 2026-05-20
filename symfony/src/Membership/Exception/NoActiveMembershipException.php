<?php

declare(strict_types=1);

namespace App\Membership\Exception;

use DomainException;
use Symfony\Component\HttpKernel\Attribute\WithHttpStatus;
use Throwable;

#[WithHttpStatus(403)]
final class NoActiveMembershipException extends DomainException
{
    public function __construct(
        string $message = 'Active membership required',
        int $code = 0,
        ?Throwable $previous = null
    )
    {
        parent::__construct($message, $code, $previous);
    }
}
