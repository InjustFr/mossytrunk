<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261002062831 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Inventory lines know whether they count a supply';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE stock_check_line ADD supply BOOLEAN DEFAULT false NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE stock_check_line DROP supply');
    }
}
