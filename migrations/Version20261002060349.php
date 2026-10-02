<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261002060349 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Sales channels offer supplies; orders use supplies';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE order_supply (id UUID NOT NULL, product_id UUID NOT NULL, variant VARCHAR(255) DEFAULT NULL, label VARCHAR(255) NOT NULL, quantity INT NOT NULL, cost_cents INT NOT NULL, order_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX order_supply_product_idx ON order_supply (product_id)');
        $this->addSql('CREATE INDEX IDX_119684598D9F6D38 ON order_supply (order_id)');
        $this->addSql('CREATE TABLE sales_channel_supply (channel_id UUID NOT NULL, product_id UUID NOT NULL, PRIMARY KEY (channel_id, product_id))');
        $this->addSql('CREATE INDEX IDX_7ED76AB272F5A1AA ON sales_channel_supply (channel_id)');
        $this->addSql('CREATE INDEX IDX_7ED76AB24584665A ON sales_channel_supply (product_id)');
        $this->addSql('ALTER TABLE order_supply ADD CONSTRAINT FK_119684598D9F6D38 FOREIGN KEY (order_id) REFERENCES "order" (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE sales_channel_supply ADD CONSTRAINT FK_7ED76AB272F5A1AA FOREIGN KEY (channel_id) REFERENCES sales_channel (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE sales_channel_supply ADD CONSTRAINT FK_7ED76AB24584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE order_supply DROP CONSTRAINT FK_119684598D9F6D38');
        $this->addSql('ALTER TABLE sales_channel_supply DROP CONSTRAINT FK_7ED76AB272F5A1AA');
        $this->addSql('ALTER TABLE sales_channel_supply DROP CONSTRAINT FK_7ED76AB24584665A');
        $this->addSql('DROP TABLE order_supply');
        $this->addSql('DROP TABLE sales_channel_supply');
    }
}
