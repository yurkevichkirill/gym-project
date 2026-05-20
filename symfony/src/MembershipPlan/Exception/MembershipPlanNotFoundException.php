<?php

declare(strict_types=1);

namespace App\MembershipPlan\Exception;

use DomainException;
use Symfony\Component\HttpKernel\Attribute\WithHttpStatus;
use Throwable;

#[WithHttpStatus(404)]
final class MembershipPlanNotFoundException extends DomainException
{
    public function __construct(
        string $message = 'Membership plan not found',
        ?Throwable $previous = null,
        int $code = 0
    )
    {
        parent::__construct($message, $code, $previous);
    }
}
