<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261002190739 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Supplier orders are priced in a currency, converted to euros at their exchange rate';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE supplier_order ADD currency VARCHAR(3) DEFAULT \'EUR\' NOT NULL');
        $this->addSql('ALTER TABLE supplier_order ADD exchange_rate_micros INT DEFAULT 1000000 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE supplier_order DROP currency');
        $this->addSql('ALTER TABLE supplier_order DROP exchange_rate_micros');
    }
}
