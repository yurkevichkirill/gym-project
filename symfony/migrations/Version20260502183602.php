<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260502183602 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX unique_item_import_job');
        $this->addSql('ALTER TABLE import_job_item DROP email');
        $this->addSql('CREATE UNIQUE INDEX unique_item_import_job ON import_job_item (row_id, job_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX unique_item_import_job');
        $this->addSql('ALTER TABLE import_job_item ADD email VARCHAR(255) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX unique_item_import_job ON import_job_item (row_id, email, job_id)');
    }
}
