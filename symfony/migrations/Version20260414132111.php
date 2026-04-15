<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260414132111 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE booking ALTER status DROP DEFAULT');
        $this->addSql('ALTER TABLE "user" ALTER price_per_hour TYPE INT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE booking ALTER status SET DEFAULT \'scheduled\'');
        $this->addSql('ALTER TABLE "user" ALTER price_per_hour TYPE NUMERIC(10, 2)');
    }
}
