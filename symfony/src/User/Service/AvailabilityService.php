<?php

declare(strict_types=1);

namespace App\User\Service;

use App\User\Entity\User;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final readonly class AvailabilityService
{
    /**
     * @throws AccessDeniedHttpException
     */
    public function ensureNotBlocked(User $user): void
    {
        if ($user->getBlockedAt() !== null) {
            throw new AccessDeniedHttpException('User is blocked');
        }
    }

    /**
     * @throws AccessDeniedHttpException
     */
    public function ensureNotDeleted(User $user): void
    {
        if ($user->getDeletedAt() !== null) {
            throw new AccessDeniedHttpException('User is deleted');
        }
    }

    /**
     * @throws AccessDeniedHttpException
     */
    public function ensureActive(User $user): void
    {
        if (!$user->isActive()) {
            throw new AccessDeniedHttpException('User is not active');
        }
    }
}
