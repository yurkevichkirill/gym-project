<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260626150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Separate trainer debt from available balance and migrate existing negative balances';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" ADD trainer_debt INT DEFAULT NULL');
        $this->addSql(<<<'SQL'
            UPDATE "user"
            SET trainer_debt = GREATEST(-COALESCE(balance, 0), 0),
                balance = GREATEST(COALESCE(balance, 0), 0)
            WHERE type = 'trainer'
            SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE "user"
            ADD CONSTRAINT CHK_TRAINER_BALANCE_AND_DEBT
            CHECK (
                type <> 'trainer'
                OR (
                    balance IS NOT NULL
                    AND balance >= 0
                    AND trainer_debt IS NOT NULL
                    AND trainer_debt >= 0
                )
            )
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" DROP CONSTRAINT CHK_TRAINER_BALANCE_AND_DEBT');
        $this->addSql(<<<'SQL'
            UPDATE "user"
            SET balance = COALESCE(balance, 0) - COALESCE(trainer_debt, 0)
            WHERE type = 'trainer'
            SQL);
        $this->addSql('ALTER TABLE "user" DROP trainer_debt');
    }
}
