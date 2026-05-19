<?php

declare(strict_types=1);

namespace App\Training\Security;

use App\Trainer\Entity\Trainer;
use App\Training\Entity\Training;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/** @extends Voter<string, Training> */
final class TrainingVoter extends Voter
{
    const string VIEW_OWN = 'TRAINING_VIEW_OWN';
    const string REMOVE_OWN = 'TRAINING_REMOVE_OWN';
    const string EDIT_OWN = 'TRAINING_EDIT_OWN';
    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::VIEW_OWN, self::REMOVE_OWN, self::EDIT_OWN], true)) {
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
        if(!$user instanceof Trainer) return false;

        /** @var Training $training **/
        $training = $subject;

        return $training->getTrainerWorkTime()->getTrainer()->getId() === $user->getId();
    }
}
