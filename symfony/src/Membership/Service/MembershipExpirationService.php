<?php

declare(strict_types=1);

namespace App\Membership\Service;

use App\Membership\Enum\MembershipStatusEnum;
use App\Membership\Repository\MembershipRepository;
use DateTimeImmutable;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;

final readonly class MembershipExpirationService
{
    public function __construct(
        public MembershipRepository $membershipRepo,
    )
    {}

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function expire(): int
    {
        $curDate = new DateTimeImmutable();
        $expiredMemberships = $this->membershipRepo->findExpired($curDate);

        foreach ($expiredMemberships as $membership) {
            if ($membership->getStatus() === MembershipStatusEnum::ACTIVE && $membership->getEndDate() <= $curDate) {
                $membership->setStatus(MembershipStatusEnum::EXPIRED);
            }
        }
        $this->membershipRepo->save();

        return count($expiredMemberships);
    }
}
