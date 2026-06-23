<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260623170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add indexes for status, expiration, soft-delete, STI and worktime lookups';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX IDX_MEMBERSHIP_STATUS_END_DATE ON membership (status, end_date)');
        $this->addSql('CREATE INDEX IDX_PAYMENT_STATUS_EXPIRES_AT ON payment (status, expires_at)');
        $this->addSql('CREATE INDEX IDX_BOOKING_STATUS ON booking (status)');
        $this->addSql('CREATE INDEX IDX_USER_DELETED_AT ON "user" (deleted_at)');
        $this->addSql('CREATE INDEX IDX_USER_TYPE ON "user" (type)');
        $this->addSql('CREATE INDEX IDX_TRAINER_WORK_TIME_DATE ON trainer_work_time (date)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_MEMBERSHIP_STATUS_END_DATE');
        $this->addSql('DROP INDEX IDX_PAYMENT_STATUS_EXPIRES_AT');
        $this->addSql('DROP INDEX IDX_BOOKING_STATUS');
        $this->addSql('DROP INDEX IDX_USER_DELETED_AT');
        $this->addSql('DROP INDEX IDX_USER_TYPE');
        $this->addSql('DROP INDEX IDX_TRAINER_WORK_TIME_DATE');
    }
}
