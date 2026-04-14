<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260414084226 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE membership ALTER status TYPE VARCHAR(23)');
        $this->addSql('ALTER TABLE payment ALTER status TYPE VARCHAR(50)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE membership ALTER status TYPE VARCHAR(7)');
        $this->addSql('ALTER TABLE payment ALTER status TYPE VARCHAR(9)');
    }
}
