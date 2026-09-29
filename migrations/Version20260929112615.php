<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260929112615 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Supplier orders get a global discount and delivery fees, shared between their lines';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE supplier_order ADD discount_cents INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE supplier_order ALTER discount_cents DROP DEFAULT');
        $this->addSql('ALTER TABLE supplier_order ADD delivery_fees_cents INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE supplier_order ALTER delivery_fees_cents DROP DEFAULT');
        $this->addSql('ALTER TABLE supplier_order_line ADD discount_share_cents INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE supplier_order_line ALTER discount_share_cents DROP DEFAULT');
        $this->addSql('ALTER TABLE supplier_order_line ADD fees_share_cents INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE supplier_order_line ALTER fees_share_cents DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE supplier_order DROP discount_cents');
        $this->addSql('ALTER TABLE supplier_order DROP delivery_fees_cents');
        $this->addSql('ALTER TABLE supplier_order_line DROP discount_share_cents');
        $this->addSql('ALTER TABLE supplier_order_line DROP fees_share_cents');
    }
}
