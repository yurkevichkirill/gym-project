<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260622143000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Restore busy training exclusion constraint per trainer worktime';
    }

    public function up(Schema $schema): void
    {
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
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE training DROP CONSTRAINT IF EXISTS training_no_busy_overlap_per_worktime');
    }
}
