<?php

declare(strict_types=1);

namespace App\Booking\Security;

use App\Admin\Entity\Admin;
use App\Booking\Entity\Booking;
use App\Client\Entity\Client;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class BookingVoter extends Voter
{
    const string VIEW = "BOOKING_VIEW";
    const string REMOVE = "BOOKING_REMOVE";
    protected function supports(string $attribute, mixed $subject): bool
    {

        if (!in_array($attribute, [self::VIEW, self::REMOVE])) {
            return false;
        }

        if (!$subject instanceof Booking) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        if (!$user instanceof Client) return false;

        /** @var Booking $booking **/
        $booking = $subject;

        return $booking->getClient()->getId() === $user->getId();
    }
}
