<?php

declare(strict_types=1);

namespace App\Security;

use App\User\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        $this->assertUserIsActive($user);
    }

    public function checkPostAuth(UserInterface $user, ?TokenInterface $token = null): void
    {
        $this->assertUserIsActive($user);
    }

    /**
     * @throws CustomUserMessageAccountStatusException
     */
    private function assertUserIsActive(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if ($user->getDeletedAt()) {
            throw new CustomUserMessageAccountStatusException('User is deleted');
        }
    }
}
