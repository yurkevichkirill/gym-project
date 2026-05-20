<?php

declare(strict_types=1);

namespace App\Trainer\Exception;

use DomainException;
use Symfony\Component\HttpKernel\Attribute\WithHttpStatus;
use Throwable;

#[WithHttpStatus(409)]
final class TrainerTimeUnavailableException extends DomainException
{
    public function __construct(
        string $message = 'This time is not available for this trainer',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
