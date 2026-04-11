<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260411144922 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE membership ADD name VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE membership ADD duration_days INT NOT NULL');
        $this->addSql('ALTER TABLE membership ADD session_limit INT DEFAULT NULL');
        $this->addSql('ALTER TABLE membership ALTER plan_id SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE membership DROP name');
        $this->addSql('ALTER TABLE membership DROP duration_days');
        $this->addSql('ALTER TABLE membership DROP session_limit');
        $this->addSql('ALTER TABLE membership ALTER plan_id DROP NOT NULL');
    }
}
