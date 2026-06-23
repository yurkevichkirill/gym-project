<?php

declare(strict_types=1);

namespace App\Training\Exception;

use DomainException;
use Symfony\Component\HttpKernel\Attribute\WithHttpStatus;

#[WithHttpStatus(400)]
final class TrainingCrossesMidnightException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Training must start and end on the same calendar day');
    }
}
