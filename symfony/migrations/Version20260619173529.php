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
        return 'Store refresh token hashes and invalidate existing refresh sessions';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DELETE FROM refresh_token');
        $this->addSql('ALTER TABLE refresh_token RENAME COLUMN token TO token_hash');
        $this->addSql(
        'ALTER TABLE refresh_token
            ALTER COLUMN token_hash TYPE VARCHAR(64)'
        );
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C74F2195B3BC57DA ON refresh_token (token_hash)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM refresh_token');
        $this->addSql('DROP INDEX UNIQ_C74F2195B3BC57DA');
        $this->addSql(
        'ALTER TABLE refresh_token RENAME COLUMN token_hash TO token'
        );
        $this->addSql(
        'ALTER TABLE refresh_token
             ALTER COLUMN token TYPE VARCHAR(1023)'
        );
    }
}
