<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260521100445 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE payment ADD original_payment_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE payment ALTER is_refund SET NOT NULL');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840D6974D42F FOREIGN KEY (original_payment_id) REFERENCES payment (id) NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_6D28840D6974D42F ON payment (original_payment_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE payment DROP CONSTRAINT FK_6D28840D6974D42F');
        $this->addSql('DROP INDEX IDX_6D28840D6974D42F');
        $this->addSql('ALTER TABLE payment DROP original_payment_id');
        $this->addSql('ALTER TABLE payment ALTER is_refund DROP NOT NULL');
    }
}
