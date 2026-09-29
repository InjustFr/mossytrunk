<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260929121240 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Selling price history per product, starting from the current price of existing products';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE product_selling_price (id UUID NOT NULL, since TIMESTAMP(0) WITH TIME ZONE NOT NULL, price_cents INT NOT NULL, product_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_79C129D84584665A ON product_selling_price (product_id)');
        $this->addSql('ALTER TABLE product_selling_price ADD CONSTRAINT FK_79C129D84584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('INSERT INTO product_selling_price (id, since, price_cents, product_id) SELECT gen_random_uuid(), created_at, selling_price_cents, id FROM product');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product_selling_price DROP CONSTRAINT FK_79C129D84584665A');
        $this->addSql('DROP TABLE product_selling_price');
    }
}
