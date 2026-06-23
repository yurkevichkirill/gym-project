<?php

declare(strict_types=1);

namespace App\Tests\Membership\Service;

use App\Membership\Entity\Membership;
use App\Membership\Service\MembershipAvailabilityService;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

final class MembershipAvailabilityServiceTest extends TestCase
{
    public function testMembershipIsAvailableOnItsEndDate(): void
    {
        $membership = $this->membership('2026-06-01', '2026-06-30');

        self::assertTrue($this->isAvailableOnDate(
            $membership,
            new DateTimeImmutable('2026-06-30 23:59:59'),
        ));
    }

    public function testMembershipIsUnavailableAfterItsEndDate(): void
    {
        $membership = $this->membership('2026-06-01', '2026-06-30');

        self::assertFalse($this->isAvailableOnDate(
            $membership,
            new DateTimeImmutable('2026-07-01 00:00:00'),
        ));
    }

    public function testMembershipIsUnavailableWhenSessionLimitIsReached(): void
    {
        $membership = $this->membership('2026-06-01', '2026-06-30');
        $membership->setSessionLimit(10);
        $membership->setVisits(10);

        self::assertFalse($this->isAvailableOnDate(
            $membership,
            new DateTimeImmutable('2026-06-15'),
        ));
    }

    private function membership(string $startDate, string $endDate): Membership
    {
        $membership = new Membership();
        $membership->setStartDate(new DateTimeImmutable($startDate));
        $membership->setEndDate(new DateTimeImmutable($endDate));
        $membership->setSessionLimit(null);

        return $membership;
    }

    private function isAvailableOnDate(
        Membership $membership,
        DateTimeImmutable $date,
    ): bool {
        $service = (new ReflectionClass(MembershipAvailabilityService::class))
            ->newInstanceWithoutConstructor();
        $method = new ReflectionMethod($service, 'isAvailableOnDate');
        $result = $method->invoke($service, $membership, $date);
        self::assertIsBool($result);

        return $result;
    }
}
