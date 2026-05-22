<?php

declare(strict_types=1);

namespace App\User\Service;

use App\Client\Entity\Client;
use App\Trainer\Entity\Trainer;
use App\User\Entity\User;
use App\User\Exception\UserBlockedException;
use App\User\Exception\UserDeletedException;
use App\User\Exception\UserNotActiveException;
use App\User\Exception\UserNotFoundException;

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
            $message = 'User is deleted';

            if ($user instanceof Trainer) {
                $message = 'Trainer is deleted';
            } else if ($user instanceof Client) {
                $message = 'Client is deleted';
            }

            throw new UserNotFoundException($message);
        }
    }

    public function ensureActive(User $user): void
    {
        if (!$user->isActive()) {
            throw new UserNotActiveException();
        }
    }
}
