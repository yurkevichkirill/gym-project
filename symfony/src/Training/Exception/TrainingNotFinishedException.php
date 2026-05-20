<?php

declare(strict_types=1);

namespace App\Training\Exception;

use DomainException;
use Symfony\Component\HttpKernel\Attribute\WithHttpStatus;
use Throwable;

#[WithHttpStatus(400)]
final class TrainingNotFinishedException extends DomainException
{
    public function __construct(
        string $message = 'Training has not happened yet',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
