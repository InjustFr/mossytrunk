<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260929131202 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Discounts are running or not from their dates only: deactivated ones now end yesterday';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE discount_rule SET valid_start = CASE WHEN valid_start > CURRENT_DATE - 1 THEN CURRENT_DATE - 1 ELSE valid_start END, valid_end = CURRENT_DATE - 1 WHERE active = false AND (valid_end IS NULL OR valid_end >= CURRENT_DATE)");
        $this->addSql('ALTER TABLE discount_rule DROP active');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE discount_rule ADD active BOOLEAN DEFAULT true NOT NULL');
    }
}
