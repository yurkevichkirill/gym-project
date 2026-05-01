<?php

declare(strict_types=1);

namespace App\Payment\Security;

use App\Client\Entity\Client;
use App\Payment\Entity\Payment;
use App\Trainer\Entity\Trainer;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class PaymentVoter extends Voter
{
    const string VIEW = "PAYMENT_VIEW";
    protected function supports(string $attribute, mixed $subject): bool
    {
        if ($attribute != self::VIEW) {
            return false;
        }

        if (!$subject instanceof Payment) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if(!$user instanceof Client && !$user instanceof Trainer) return false;

        /** @var Payment $payment **/
        $payment = $subject;

        return $payment->getClient()?->getId() === $user->getId() || $payment->getTrainer()?->getId() === $user->getId();
    }
}
