<?php

declare(strict_types=1);

namespace App\Membership\Exception;

use DomainException;
use Symfony\Component\HttpKernel\Attribute\WithHttpStatus;
use Throwable;

#[WithHttpStatus(409)]
final class MembershipActiveException extends DomainException
{
    public function __construct(
        string $message = 'Client already have active, frozen or pending membership',
        ?Throwable $previous = null,
        int $code = 0
    )
    {
        parent::__construct($message, $code, $previous);
    }
}
