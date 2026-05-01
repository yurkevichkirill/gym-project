<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260501104226 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE membership ALTER plan_id DROP NOT NULL');
        $this->addSql('ALTER TABLE membership_plan ALTER price TYPE INT');
        $this->addSql('ALTER TABLE training ADD status VARCHAR(50) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE membership ALTER plan_id SET NOT NULL');
        $this->addSql('ALTER TABLE membership_plan ALTER price TYPE NUMERIC(10, 2)');
        $this->addSql('ALTER TABLE training DROP status');
    }
}
