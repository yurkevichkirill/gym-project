<?php

declare(strict_types=1);

namespace App\MembershipPlan\Service;

use App\MembershipPlan\DTO\CreateMembershipPlanRequestDTO;
use App\MembershipPlan\DTO\UpdateMembershipPlanRequestDTO;
use App\MembershipPlan\Entity\MembershipPlan;
use App\MembershipPlan\Exception\MembershipPlanInUseException;
use App\MembershipPlan\Repository\MembershipPlanRepository;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;

final readonly class MembershipPlanManager
{
    public function __construct(
        private MembershipPlanRepository $repo,
        private EntityManagerInterface  $entityManager,
    )
    {}

    public function create(CreateMembershipPlanRequestDTO $dto): MembershipPlan
    {
        $membershipPlan = new MembershipPlan();

        $membershipPlan->setName($dto->name);
        $membershipPlan->setPrice($dto->price);
        $membershipPlan->setSessionLimit($dto->sessionLimit);
        $membershipPlan->setDurationDays($dto->durationDays);

        $this->repo->create($membershipPlan);

        $this->entityManager->flush();

        return $membershipPlan;
    }

    public function update(UpdateMembershipPlanRequestDTO $requestDto, MembershipPlan $membershipPlan): MembershipPlan
    {
        if ($requestDto->durationDays !== null) {
            $membershipPlan->setDurationDays($requestDto->durationDays);
        }
        if ($requestDto->name !== null) {
            $membershipPlan->setName($requestDto->name);
        }
        if ($requestDto->sessionLimit !== null) {
            $membershipPlan->setSessionLimit($requestDto->sessionLimit);
        }
        if ($requestDto->price !== null) {
            $membershipPlan->setPrice($requestDto->price);
        }

        $this->entityManager->flush();

        return $membershipPlan;
    }

    public function remove(MembershipPlan $membershipPlan): void
    {
        if ($this->repo->existsForPlan($membershipPlan)) {
            throw new MembershipPlanInUseException();
        }

        try {
            $this->repo->remove($membershipPlan);
            $this->entityManager->flush();
        } catch (ForeignKeyConstraintViolationException $exception) {
            throw new MembershipPlanInUseException(previous: $exception);
        }
    }
}
