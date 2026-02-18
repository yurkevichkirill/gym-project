<?php

declare(strict_types=1);

namespace App\Trainer\Security;

use App\Trainer\Entity\Trainer;
use App\Training\Entity\Training;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;

class TrainerVoter
{
    const string REMOVE = "TRAINER_REMOVE";
    const string EDIT = "TRAINER_EDIT";
    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::REMOVE, self::EDIT])) {
            return false;
        }

        if (!$subject instanceof Trainer) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if(!$user instanceof Trainer) return false;

        /** @var Trainer $trainer **/
        $trainer = $subject;

        return $trainer->getId() === $user->getId();
    }
}
