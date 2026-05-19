<?php

declare(strict_types=1);

namespace App\Membership\Schedule;

use App\Membership\Message\ExpireMembershipsMessage;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

#[AsSchedule('memberships')]
final readonly class MembershipScheduleProvider implements ScheduleProviderInterface
{
    public function getSchedule(): Schedule
    {
        return new Schedule()
            ->add(RecurringMessage::cron('@daily', new ExpireMembershipsMessage()));
    }
}
