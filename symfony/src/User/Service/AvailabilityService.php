<?php

declare(strict_types=1);

namespace App\User\Service;

use App\User\Entity\User;
use App\User\Exception\UserBlockedException;
use App\User\Exception\UserDeletedException;
use App\User\Exception\UserNotActiveException;

final readonly class AvailabilityService
{
    public function ensureNotBlocked(User $user): void
    {
        if ($user->getBlockedAt() !== null) {
            throw new UserBlockedException();
        }
    }

    public function ensureNotDeleted(User $user): void
    {
        if ($user->getDeletedAt() !== null) {
            throw new UserDeletedException();
        }
    }

    public function ensureActive(User $user): void
    {
        if (!$user->isActive()) {
            throw new UserNotActiveException();
        }
    }
}
