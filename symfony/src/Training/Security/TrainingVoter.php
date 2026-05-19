<?php

declare(strict_types=1);

namespace App\Training\Security;

use App\Trainer\Entity\Trainer;
use App\Training\Entity\Training;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class TrainingVoter extends Voter
{
    const string VIEW = "TRAINING_VIEW";
    const string REMOVE = "TRAINING_REMOVE";
    const string EDIT = "TRAINING_EDIT";
    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::VIEW, self::REMOVE, self::EDIT])) {
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
