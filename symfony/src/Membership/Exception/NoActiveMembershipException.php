<?php

declare(strict_types=1);

namespace App\Membership\Exception;

use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

final class NoActiveMembershipException extends ConflictHttpException
{
    public function __construct(string $message = 'Active membership required', ?\Throwable $previous = null, int $code = 0, array $headers = [])
    {
        parent::__construct($message, $previous, $code, $headers);
    }
}
