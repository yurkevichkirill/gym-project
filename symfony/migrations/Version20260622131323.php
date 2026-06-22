<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260622131323 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE membership DROP CONSTRAINT fk_86ffd285e899029b');
        $this->addSql('ALTER TABLE membership ADD CONSTRAINT FK_86FFD285E899029B FOREIGN KEY (plan_id) REFERENCES membership_plan (id) ON DELETE RESTRICT NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE membership DROP CONSTRAINT FK_86FFD285E899029B');
        $this->addSql('ALTER TABLE membership ADD CONSTRAINT fk_86ffd285e899029b FOREIGN KEY (plan_id) REFERENCES membership_plan (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
