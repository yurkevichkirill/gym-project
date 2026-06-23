<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260623120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Restore busy-training overlap protection and add single-day training and unique phone safeguards';
    }

    public function up(Schema $schema): void
    {
        $duplicatePhone = $this->connection->fetchOne(<<<'SQL'
            SELECT phone
            FROM "user"
            GROUP BY phone
            HAVING COUNT(*) > 1
            LIMIT 1
        SQL);

        $this->abortIf(
            $duplicatePhone !== false,
            'Cannot add the unique phone constraint while duplicate phone numbers exist.',
        );

        $crossingTrainingId = $this->connection->fetchOne(<<<'SQL'
            SELECT id
            FROM training
            WHERE EXTRACT(EPOCH FROM start_time)::int + duration_minutes * 60 >= 86400
            LIMIT 1
        SQL);

        $this->abortIf(
            $crossingTrainingId !== false,
            'Cannot add the single-day training constraint while a training crosses midnight.',
        );

        $this->addSql('CREATE EXTENSION IF NOT EXISTS btree_gist');
        $this->addSql('ALTER TABLE training DROP CONSTRAINT IF EXISTS training_no_busy_overlap_per_worktime');
        $this->addSql(<<<'SQL'
            ALTER TABLE training
            ADD CONSTRAINT training_no_busy_overlap_per_worktime
            EXCLUDE USING gist (
                trainer_work_time_id WITH =,
                int4range(
                    (EXTRACT(EPOCH FROM start_time)::int / 60),
                    (EXTRACT(EPOCH FROM start_time)::int / 60) + duration_minutes,
                    '[)'
                ) WITH &&
            )
            WHERE (is_busy = true)
        SQL);

        $this->addSql('ALTER TABLE training DROP CONSTRAINT IF EXISTS training_within_single_day');
        $this->addSql(<<<'SQL'
            ALTER TABLE training
            ADD CONSTRAINT training_within_single_day
            CHECK (EXTRACT(EPOCH FROM start_time)::int + duration_minutes * 60 < 86400)
        SQL);

        $this->addSql('DROP INDEX IF EXISTS UNIQ_USER_PHONE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_USER_PHONE ON "user" (phone)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS UNIQ_USER_PHONE');
        $this->addSql('ALTER TABLE training DROP CONSTRAINT IF EXISTS training_within_single_day');
        $this->addSql('ALTER TABLE training DROP CONSTRAINT IF EXISTS training_no_busy_overlap_per_worktime');
    }
}
