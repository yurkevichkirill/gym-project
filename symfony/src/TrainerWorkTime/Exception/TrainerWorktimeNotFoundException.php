<?php

declare(strict_types=1);

namespace App\TrainerWorkTime\Exception;

use DomainException;
use Symfony\Component\HttpKernel\Attribute\WithHttpStatus;
use Throwable;

#[WithHttpStatus(404)]
final class TrainerWorktimeNotFoundException extends DomainException
{
    public function __construct(
        string $message = 'Trainer worktime not found for the requested date.',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
