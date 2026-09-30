<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260930150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Archived products, product types and variants; supplier references on supplier orders';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product ADD archived_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE product_type ADD archived_variants JSON DEFAULT \'[]\' NOT NULL');
        $this->addSql('ALTER TABLE product_type ADD archived_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE supplier_order ADD supplier_reference VARCHAR(100) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product DROP archived_at');
        $this->addSql('ALTER TABLE product_type DROP archived_variants');
        $this->addSql('ALTER TABLE product_type DROP archived_at');
        $this->addSql('ALTER TABLE supplier_order DROP supplier_reference');
    }
}
