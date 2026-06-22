<?php

declare(strict_types=1);

namespace App\TrainerWorkTime\Exception;

use DomainException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\WithHttpStatus;
use Throwable;

#[WithHttpStatus(Response::HTTP_CONFLICT)]
final class WorktimeHasActiveTrainingsException extends DomainException
{
    public function __construct(
        string $message = 'Trainer worktime has associated training history',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
