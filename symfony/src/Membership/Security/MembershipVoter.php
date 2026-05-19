<?php

declare(strict_types=1);

namespace App\Membership\Security;

use App\Client\Entity\Client;
use App\Membership\Entity\Membership;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class MembershipVoter extends Voter
{
    const string VIEW = 'MEMBERSHIP_VIEW';
    const string REMOVE = 'MEMBERSHIP_REMOVE';
    const string EDIT = 'MEMBERSHIP_EDIT';
    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::VIEW, self::REMOVE, self::EDIT])) {
            return false;
        }

        if (!$subject instanceof Membership) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if(!$user instanceof Client) return false;

        /** @var Membership $membership **/
        $membership = $subject;

        return $membership->getClient()->getId() === $user->getId();
    }
}
