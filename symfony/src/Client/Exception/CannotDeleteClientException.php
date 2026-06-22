<?php

declare(strict_types=1);

namespace App\Client\Exception;

use DomainException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\WithHttpStatus;
use Throwable;

#[WithHttpStatus(Response::HTTP_CONFLICT)]
final class CannotDeleteClientException extends DomainException
{
    public function __construct(
        string $message = 'Cannot delete client account',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
