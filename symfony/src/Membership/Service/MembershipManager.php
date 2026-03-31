<?php

declare(strict_types=1);

namespace App\Membership\Service;

use App\Client\Entity\Client;
use App\Client\Service\ClientManager;
use App\Exception\InvalidMembershipStatusException;
use App\Exception\MembershipAlreadyActiveException;
use App\Membership\DTO\UpdateMembershipRequest;
use App\Membership\Entity\Membership;
use App\Membership\Enum\MembershipStatusEnum;
use App\Membership\Repository\MembershipRepository;
use App\MembershipPlan\Repository\MembershipPlanRepository;
use DateTimeImmutable;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;

final readonly class MembershipManager
{
    public function __construct(
        private MembershipRepository $membershipRepo,
        private MembershipPlanRepository $membershipPlanRepo,
        private ClientManager $clientManager,
    )
    {}

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function create(Client $client, int $membershipPlanId): Membership
    {
        $this->validateClientHasActiveMembership($client);

        $plan = $this->membershipPlanRepo->find($membershipPlanId);
        $payment = $this->clientManager->pay($client, (float) $plan->getPrice());

        $membership = new Membership();
        $membership->setClient($client);
        $membership->setPlan($plan);
        $membership->setPayment($payment);
        $this->membershipRepo->create($membership);

        return $membership;
    }

    private function validateClientHasActiveMembership($client): void
    {
        $membership = $this->membershipRepo->findOneBy([
            'client' => $client,
            'status' => MembershipStatusEnum::ACTIVE
        ]);

        if ($membership) {
            throw new MembershipAlreadyActiveException();
        }
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function update(Membership $membership, UpdateMembershipRequest $requestDto): Membership
    {
        if ($membership->getStatus() === MembershipStatusEnum::EXPIRED && $requestDto->status === MembershipStatusEnum::ACTIVE) {
            return $this->renew($membership);
        }
        return match ($requestDto->status) {
            MembershipStatusEnum::FROZEN => $this->freeze($membership),
            MembershipStatusEnum::ACTIVE => $this->unfreeze($membership),
            MembershipStatusEnum::EXPIRED => throw new InvalidMembershipStatusException('Membership cannot be set to expired'),
        };
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function freeze(Membership $membership): Membership
    {
        if ($membership->getStatus() != MembershipStatusEnum::ACTIVE) {
            throw new InvalidMembershipStatusException();
        }

        $membership->setFrozenAt(new DateTimeImmutable());
        $membership->setStatus(MembershipStatusEnum::FROZEN);
        $this->membershipRepo->save();

        return $membership;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function unfreeze(Membership $membership): Membership
    {
        if ($membership->getStatus() != MembershipStatusEnum::FROZEN) {
            throw new InvalidMembershipStatusException("Only frozen membership can be unfrozen");
        }

        $dateInterval = $membership->getFrozenAt()->diff(new DateTimeImmutable());
        $membership->setEndDate($membership->getEndDate()->add($dateInterval));
        $membership->setFrozenAt(null);
        $membership->setStatus(MembershipStatusEnum::ACTIVE);
        $this->membershipRepo->save();

        return $membership;
    }

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function renew(Membership $membership): Membership
    {
        return $this->create($membership->getClient(), $membership->getPlan()->getId());
    }
}
