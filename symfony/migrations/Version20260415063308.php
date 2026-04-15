<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260415063308 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE booking ALTER booked_at DROP DEFAULT');
        $this->addSql('ALTER TABLE booking ALTER booked_at DROP NOT NULL');
        $this->addSql('ALTER TABLE booking ALTER payment_id DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE booking ALTER booked_at SET DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE booking ALTER booked_at SET NOT NULL');
        $this->addSql('ALTER TABLE booking ALTER payment_id SET NOT NULL');
    }
}
