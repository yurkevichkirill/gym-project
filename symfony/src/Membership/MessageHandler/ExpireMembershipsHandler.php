<?php

declare(strict_types=1);

namespace App\Membership\MessageHandler;

use App\Membership\Message\ExpireMembershipsMessage;
use App\Membership\Service\MembershipExpirationService;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class ExpireMembershipsHandler
{
    public function __construct(
        private MembershipExpirationService $service,
    )
    {}

    /**
     * @throws OptimisticLockException
     * @throws ORMException
     */
    public function __invoke(ExpireMembershipsMessage $message): void
    {
        $this->service->expire();
    }
}
