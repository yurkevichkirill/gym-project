<?php

declare(strict_types=1);

namespace App\User\Service;

use App\User\Entity\User;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final readonly class AvailabilityService
{
    public function ensureNotBlocked(User $user): void
    {
        if ($user->getBlockedAt() !== null) {
            throw new AccessDeniedHttpException("User is blocked");
        }
    }
}
