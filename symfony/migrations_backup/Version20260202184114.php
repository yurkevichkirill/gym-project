<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260202184114 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // 1. TrainerWorkTime
        $this->addSql('DROP INDEX unique_trainer_day');
        $this->addSql('ALTER TABLE trainer_work_time DROP day_of_week');
        $this->addSql('ALTER TABLE trainer_work_time ADD date TIME(0) WITHOUT TIME ZONE NOT NULL DEFAULT \'09:00:00\'');
        $this->addSql('CREATE UNIQUE INDEX unique_trainer_day ON trainer_work_time (trainer_id, date)');

        // 2. ✅ ОЧИСТИ ВСЁ перед FK!
        $this->addSql('DELETE FROM booking');           // Каскадно очистит training если ON DELETE CASCADE
        $this->addSql('DELETE FROM training');
        $this->addSql('DELETE FROM trainer_work_time WHERE trainer_id NOT IN (SELECT id FROM trainer)');

        // 3. Training изменения (теперь OK)
        $this->addSql('ALTER TABLE training DROP CONSTRAINT fk_d5128a8ffb08edf6');
        $this->addSql('DROP INDEX idx_d5128a8ffb08edf6');
        $this->addSql('ALTER TABLE training DROP day_of_week');
        $this->addSql('ALTER TABLE training RENAME COLUMN trainer_id TO trainer_work_time_id');
        $this->addSql('ALTER TABLE training ADD CONSTRAINT FK_D5128A8F253F97D7 FOREIGN KEY (trainer_work_time_id) REFERENCES trainer_work_time (id)');
        $this->addSql('CREATE INDEX IDX_D5128A8F253F97D7 ON training (trainer_work_time_id)');
    }


    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX unique_trainer_day');
        $this->addSql('ALTER TABLE trainer_work_time ADD day_of_week VARCHAR(9) NOT NULL');
        $this->addSql('ALTER TABLE trainer_work_time DROP date');
        $this->addSql('CREATE UNIQUE INDEX unique_trainer_day ON trainer_work_time (trainer_id, day_of_week)');
        $this->addSql('ALTER TABLE training DROP CONSTRAINT FK_D5128A8F253F97D7');
        $this->addSql('DROP INDEX IDX_D5128A8F253F97D7');
        $this->addSql('ALTER TABLE training ADD day_of_week VARCHAR(9) NOT NULL');
        $this->addSql('ALTER TABLE training RENAME COLUMN trainer_work_time_id TO trainer_id');
        $this->addSql('ALTER TABLE training ADD CONSTRAINT fk_d5128a8ffb08edf6 FOREIGN KEY (trainer_id) REFERENCES trainer (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_d5128a8ffb08edf6 ON training (trainer_id)');
    }
}
