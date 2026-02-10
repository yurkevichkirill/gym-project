<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260116163815 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE trainer DROP CONSTRAINT fk_c5150820cbf6a3c6');
        $this->addSql('DROP INDEX idx_c5150820cbf6a3c6');
        $this->addSql('ALTER TABLE trainer RENAME COLUMN training_type_id_id TO training_type_id');
        $this->addSql('ALTER TABLE trainer ADD CONSTRAINT FK_C515082018721C9D FOREIGN KEY (training_type_id) REFERENCES training_type (id) NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_C515082018721C9D ON trainer (training_type_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE trainer DROP CONSTRAINT FK_C515082018721C9D');
        $this->addSql('DROP INDEX IDX_C515082018721C9D');
        $this->addSql('ALTER TABLE trainer RENAME COLUMN training_type_id TO training_type_id_id');
        $this->addSql('ALTER TABLE trainer ADD CONSTRAINT fk_c5150820cbf6a3c6 FOREIGN KEY (training_type_id_id) REFERENCES training_type (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_c5150820cbf6a3c6 ON trainer (training_type_id_id)');
    }
}
