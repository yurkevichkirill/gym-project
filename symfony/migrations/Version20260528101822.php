<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260528101822 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX uniq_active_client_membership');
        $this->addSql('CREATE UNIQUE INDEX uniq_active_client_membership ON membership (client_id) WHERE status IN (\'active\', \'frozen\')');
        $this->addSql('DROP INDEX uniq_refund_original_payment');
        $this->addSql('CREATE UNIQUE INDEX uniq_refund_original_payment ON payment (original_payment_id) WHERE is_refund = true');
        $this->addSql('ALTER TABLE training_type ALTER photo_url DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX uniq_active_client_membership');
        $this->addSql('CREATE UNIQUE INDEX uniq_active_client_membership ON membership (client_id) WHERE ((status)::text = ANY ((ARRAY[\'active\'::character varying, \'frozen\'::character varying])::text[]))');
        $this->addSql('DROP INDEX uniq_refund_original_payment');
        $this->addSql('CREATE UNIQUE INDEX uniq_refund_original_payment ON payment (original_payment_id) WHERE (is_refund = true)');
        $this->addSql('ALTER TABLE training_type ALTER photo_url SET NOT NULL');
    }
}
