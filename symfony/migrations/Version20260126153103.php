<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260126153103 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE training DROP CONSTRAINT fk_d5128a8f26c381de');
        $this->addSql('DROP INDEX idx_d5128a8f26c381de');
        $this->addSql('ALTER TABLE training RENAME COLUMN trainer_availability_id TO trainer_id');
        $this->addSql('ALTER TABLE training ADD CONSTRAINT FK_D5128A8FFB08EDF6 FOREIGN KEY (trainer_id) REFERENCES trainer (id) NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_D5128A8FFB08EDF6 ON training (trainer_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE training DROP CONSTRAINT FK_D5128A8FFB08EDF6');
        $this->addSql('DROP INDEX IDX_D5128A8FFB08EDF6');
        $this->addSql('ALTER TABLE training RENAME COLUMN trainer_id TO trainer_availability_id');
        $this->addSql('ALTER TABLE training ADD CONSTRAINT fk_d5128a8f26c381de FOREIGN KEY (trainer_availability_id) REFERENCES trainer_availability (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_d5128a8f26c381de ON training (trainer_availability_id)');
    }
}
