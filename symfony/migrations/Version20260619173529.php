<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260619173529 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE refresh_token ADD token_hash VARCHAR(64) NOT NULL');
        $this->addSql('ALTER TABLE refresh_token DROP token');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C74F2195B3BC57DA ON refresh_token (token_hash)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_C74F2195B3BC57DA');
        $this->addSql('ALTER TABLE refresh_token ADD token VARCHAR(1023) NOT NULL');
        $this->addSql('ALTER TABLE refresh_token DROP token_hash');
    }
}
