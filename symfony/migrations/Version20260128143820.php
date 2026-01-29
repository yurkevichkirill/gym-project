<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260128143820 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE membership DROP CONSTRAINT fk_86ffd2854c3a3bb');
        $this->addSql('DROP INDEX uniq_86ffd2854c3a3bb');
        $this->addSql('ALTER TABLE membership ADD session_limit INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE membership DROP payment_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE membership ADD payment_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE membership DROP session_limit');
        $this->addSql('ALTER TABLE membership ADD CONSTRAINT fk_86ffd2854c3a3bb FOREIGN KEY (payment_id) REFERENCES payment (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX uniq_86ffd2854c3a3bb ON membership (payment_id)');
    }
}
