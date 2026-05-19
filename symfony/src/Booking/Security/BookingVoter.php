<?php

declare(strict_types=1);

namespace App\Booking\Security;

use App\Admin\Entity\Admin;
use App\Booking\Entity\Booking;
use App\Client\Entity\Client;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class BookingVoter extends Voter
{
    const string VIEW_OWN = "BOOKING_VIEW_OWN";
    const string CANCEL_OWN = "BOOKING_CANCEL_OWN";
    const string VIEW_ADMIN = "BOOKING_VIEW_ADMIN";
    const string CANCEL_ADMIN = "BOOKING_CANCEL_ADMIN";
    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::VIEW_OWN, self::CANCEL_OWN, self::VIEW_ADMIN, self::CANCEL_ADMIN])) {
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

        if (!$user instanceof Client && !$user instanceof Admin) return false;

        if ($user instanceof Admin && in_array($attribute, [self::VIEW_ADMIN, self::CANCEL_ADMIN])) {
            return true;
        }

        /** @var Booking $booking **/
        $booking = $subject;

        return $booking->getClient()->getId() === $user->getId();
    }
}
