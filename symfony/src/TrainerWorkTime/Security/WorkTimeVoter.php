<?php

declare(strict_types=1);

namespace App\TrainerWorkTime\Security;

use App\Trainer\Entity\Trainer;
use App\TrainerWorkTime\Entity\TrainerWorkTime;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class WorkTimeVoter extends Voter
{
    const string EDIT = "WORKTIME_EDIT";
    const string REMOVE = "WORKTIME_REMOVE";
    /**
     * @inheritDoc
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::EDIT, self::REMOVE])) {
            return false;
        }

        if (!$subject instanceof TrainerWorkTime) {
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();
        if(!$user instanceof Trainer) return false;

        /** @var TrainerWorkTime $worktime **/
        $worktime = $subject;

        return $worktime->getTrainer()->getId() === $user->getId();
    }
}
