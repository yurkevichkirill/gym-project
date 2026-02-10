<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260119145447 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE booking ADD payment_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE booking ADD CONSTRAINT FK_E00CEDDE4C3A3BB FOREIGN KEY (payment_id) REFERENCES payment (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_E00CEDDE4C3A3BB ON booking (payment_id)');
        $this->addSql('ALTER TABLE membership ADD payment_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE membership ADD CONSTRAINT FK_86FFD2854C3A3BB FOREIGN KEY (payment_id) REFERENCES payment (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_86FFD2854C3A3BB ON membership (payment_id)');
        $this->addSql('ALTER TABLE payment DROP CONSTRAINT fk_6d28840d3301c60');
        $this->addSql('ALTER TABLE payment DROP CONSTRAINT fk_6d28840d1fb354cd');
        $this->addSql('DROP INDEX uniq_6d28840d1fb354cd');
        $this->addSql('DROP INDEX uniq_6d28840d3301c60');
        $this->addSql('ALTER TABLE payment DROP membership_id');
        $this->addSql('ALTER TABLE payment DROP booking_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE booking DROP CONSTRAINT FK_E00CEDDE4C3A3BB');
        $this->addSql('DROP INDEX UNIQ_E00CEDDE4C3A3BB');
        $this->addSql('ALTER TABLE booking DROP payment_id');
        $this->addSql('ALTER TABLE membership DROP CONSTRAINT FK_86FFD2854C3A3BB');
        $this->addSql('DROP INDEX UNIQ_86FFD2854C3A3BB');
        $this->addSql('ALTER TABLE membership DROP payment_id');
        $this->addSql('ALTER TABLE payment ADD membership_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE payment ADD booking_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT fk_6d28840d3301c60 FOREIGN KEY (booking_id) REFERENCES booking (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT fk_6d28840d1fb354cd FOREIGN KEY (membership_id) REFERENCES membership (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX uniq_6d28840d1fb354cd ON payment (membership_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_6d28840d3301c60 ON payment (booking_id)');
    }
}
