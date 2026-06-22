<?php

declare(strict_types=1);

namespace App\MembershipPlan\Exception;

use DomainException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\WithHttpStatus;
use Throwable;

#[WithHttpStatus(Response::HTTP_CONFLICT)]
final class MembershipPlanInUseException extends DomainException
{
    public function __construct(
        string $message = 'Membership plan cannot be deleted because it is used by memberships',
        ?Throwable $previous = null,
        int $code = 0
    ) {
        parent::__construct($message, $code, $previous);
    }
}
