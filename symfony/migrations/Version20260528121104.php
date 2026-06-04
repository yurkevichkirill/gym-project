<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260528121104 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS uniq_active_client_membership');
        $this->addSql('DROP INDEX IF EXISTS uniq_refund_original_payment');

        $this->addSql('CREATE UNIQUE INDEX uniq_active_client_membership ON membership (client_id) WHERE status IN (\'active\', \'frozen\', \'pending\')');
        $this->addSql('CREATE UNIQUE INDEX uniq_refund_original_payment ON payment (original_payment_id) WHERE is_refund = true');

        $this->addSql('ALTER TABLE "user" RENAME COLUMN photo_url TO photo_path');
        $this->addSql('ALTER TABLE training_type ALTER photo_url DROP NOT NULL');
        $this->addSql('ALTER TABLE training_type RENAME COLUMN photo_url TO photo_path');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS uniq_active_client_membership');
        $this->addSql('DROP INDEX IF EXISTS uniq_refund_original_payment');

        $this->addSql('CREATE UNIQUE INDEX uniq_active_client_membership ON membership (client_id) WHERE status IN (\'active\', \'frozen\')');
        $this->addSql('CREATE UNIQUE INDEX uniq_refund_original_payment ON payment (original_payment_id) WHERE is_refund = true');

        $this->addSql('ALTER TABLE "user" RENAME COLUMN photo_path TO photo_url');
        $this->addSql('ALTER TABLE training_type RENAME COLUMN photo_path TO photo_url');
        $this->addSql('ALTER TABLE training_type ALTER photo_url SET NOT NULL');
    }
}
