<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260129074100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE booking DROP CONSTRAINT fk_e00cedde4c3a3bb');
        $this->addSql('DROP INDEX uniq_e00cedde4c3a3bb');
        $this->addSql('ALTER TABLE booking DROP payment_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE booking ADD payment_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE booking ADD CONSTRAINT fk_e00cedde4c3a3bb FOREIGN KEY (payment_id) REFERENCES payment (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX uniq_e00cedde4c3a3bb ON booking (payment_id)');
    }
}
