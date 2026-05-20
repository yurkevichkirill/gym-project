<?php

declare(strict_types=1);

namespace App\TrainerWorkTime\Exception;

use DomainException;
use Symfony\Component\HttpKernel\Attribute\WithHttpStatus;
use Throwable;

#[WithHttpStatus(422)]
final class PastWorktimeDateException extends DomainException
{
    public function __construct(
        string $message = 'Cannot create worktime in the past',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}

