<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260619193000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store refresh token expiration with time precision';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE refresh_token ALTER COLUMN expires_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE refresh_token ALTER COLUMN expires_at TYPE DATE');
    }
}
