<?php

declare(strict_types=1);

namespace App\Payment\Security;

use App\Client\Entity\Client;
use App\Payment\Entity\Payment;
use App\Trainer\Entity\Trainer;
use App\Training\Entity\Training;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;

class PaymentVoter
{
    const string VIEW = "PAYMENT_VIEW";
    protected function supports(string $attribute, mixed $subject): bool
    {
        if ($attribute != self::VIEW) {
            return false;
        }

        if (!$subject instanceof Training) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if(!$user instanceof Client) return false;

        /** @var Payment $payment **/
        $payment = $subject;

        return $payment->getClient()->getId() === $user->getId();
    }
}
