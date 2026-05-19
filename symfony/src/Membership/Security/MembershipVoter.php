<?php

declare(strict_types=1);

namespace App\Membership\Security;

use App\Client\Entity\Client;
use App\Membership\Entity\Membership;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/** @extends Voter<string, Membership> */
final class MembershipVoter extends Voter
{
    const string VIEW_OWN = 'MEMBERSHIP_VIEW_OWN';
    const string EDIT_OWN = 'MEMBERSHIP_EDIT_OWN';
    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::VIEW_OWN, self::EDIT_OWN], true)) {
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
