<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260619194500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Track revoked refresh tokens for reuse detection';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE refresh_token ADD revoked_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE refresh_token DROP revoked_at');
    }
}
