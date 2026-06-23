<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DatabaseSafeguardsTest extends KernelTestCase
{
    public function testBookingAndPhoneSafeguardsAreInstalled(): void
    {
        self::bootKernel();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $connection = $entityManager->getConnection();

        $constraintNames = $connection->fetchFirstColumn(<<<'SQL'
            SELECT conname
            FROM pg_constraint
            WHERE conname IN (
                'training_no_busy_overlap_per_worktime',
                'training_within_single_day'
            )
            ORDER BY conname
        SQL);

        self::assertSame([
            'training_no_busy_overlap_per_worktime',
            'training_within_single_day',
        ], $constraintNames);

        $phoneIndexCount = $connection->fetchOne(<<<'SQL'
            SELECT COUNT(*)
            FROM pg_indexes
            WHERE schemaname = current_schema()
              AND lower(indexname) = lower('UNIQ_USER_PHONE')
        SQL);

        self::assertSame(1, (int) $phoneIndexCount);
    }
}
