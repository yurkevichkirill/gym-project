<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260220081855 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE payment ADD client_full_name VARCHAR(200) NOT NULL');
        $this->addSql('ALTER TABLE payment ADD client_email VARCHAR(180) NOT NULL');
        $this->addSql('ALTER TABLE payment ADD client_phone VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE payment ADD trainer_full_name VARCHAR(200) DEFAULT NULL');
        $this->addSql('ALTER TABLE payment ADD trainer_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840DFB08EDF6 FOREIGN KEY (trainer_id) REFERENCES "user" (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_6D28840DFB08EDF6 ON payment (trainer_id)');
        $this->addSql('ALTER TABLE "user" RENAME COLUMN price TO price_per_hour');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE payment DROP CONSTRAINT FK_6D28840DFB08EDF6');
        $this->addSql('DROP INDEX IDX_6D28840DFB08EDF6');
        $this->addSql('ALTER TABLE payment DROP client_full_name');
        $this->addSql('ALTER TABLE payment DROP client_email');
        $this->addSql('ALTER TABLE payment DROP client_phone');
        $this->addSql('ALTER TABLE payment DROP trainer_full_name');
        $this->addSql('ALTER TABLE payment DROP trainer_id');
        $this->addSql('ALTER TABLE "user" RENAME COLUMN price_per_hour TO price');
    }
}
