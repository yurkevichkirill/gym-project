<?php

declare(strict_types=1);

namespace App\MembershipPlan\Service;

use App\MembershipPlan\DTO\CreateMembershipPlanRequest;
use App\MembershipPlan\Entity\MembershipPlan;
use App\MembershipPlan\Repository\MembershipPlanRepository;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;

final readonly class MembershipPlanManager
{
    public function __construct(
        private MembershipPlanRepository $repo,
    )
    {}

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function create(CreateMembershipPlanRequest $dto): MembershipPlan
    {
        $membershipPlan = new MembershipPlan();

        $membershipPlan->setName($dto->name);
        $membershipPlan->setPrice($dto->price);
        $membershipPlan->setSessionLimit($dto->sessionLimit);
        $membershipPlan->setDurationDays($dto->durationDays);

        $this->repo->create($membershipPlan);

        return $membershipPlan;
    }

    public function update(CreateMembershipPlanRequest $dto, MembershipPlan $membershipPlan): MembershipPlan
    {

    }

}
