<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260622131506 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE training DROP CONSTRAINT IF EXISTS training_no_busy_overlap_per_worktime');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE EXTENSION IF NOT EXISTS btree_gist');
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
}
