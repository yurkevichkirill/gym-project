<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260413154318 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE membership ALTER status DROP DEFAULT');
        $this->addSql('ALTER TABLE membership ALTER payment_id DROP NOT NULL');
        $this->addSql('ALTER TABLE payment ADD created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE payment ADD stripe_payment_intent_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE payment ADD currency VARCHAR(10) NOT NULL');
        $this->addSql('ALTER TABLE payment ADD status VARCHAR(9) NOT NULL');
        $this->addSql('ALTER TABLE payment ADD confirmed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE payment ALTER amount TYPE INT USING ROUND(amount * 100)::INT');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_6D28840DFC72F97E ON payment (stripe_payment_intent_id)');
        $this->addSql('ALTER TABLE "user" ALTER balance TYPE INT USING ROUND(balance * 100)::INT');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE membership ALTER status SET DEFAULT \'active\'');
        $this->addSql('ALTER TABLE membership ALTER payment_id SET NOT NULL');
        $this->addSql('DROP INDEX UNIQ_6D28840DFC72F97E');
        $this->addSql('ALTER TABLE payment DROP created_at');
        $this->addSql('ALTER TABLE payment DROP stripe_payment_intent_id');
        $this->addSql('ALTER TABLE payment DROP currency');
        $this->addSql('ALTER TABLE payment DROP status');
        $this->addSql('ALTER TABLE payment DROP confirmed_at');
        $this->addSql('ALTER TABLE payment ALTER amount TYPE NUMERIC(10, 2) USING amount / 100.0');
        $this->addSql('ALTER TABLE "user" ALTER balance TYPE NUMERIC(10, 2) USING balance / 100.0');
    }
}
