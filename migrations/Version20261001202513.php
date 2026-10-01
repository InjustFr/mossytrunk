<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261001202513 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Sales channels with their own product prices; current prices and event orders move to a main market channel';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE sales_channel (id UUID NOT NULL, name VARCHAR(100) NOT NULL, service VARCHAR(32) DEFAULT NULL, kind VARCHAR(16) DEFAULT \'online\' NOT NULL, main BOOLEAN DEFAULT false NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, workspace_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX sales_channel_workspace_name ON sales_channel (workspace_id, name)');
        $this->addSql('CREATE UNIQUE INDEX sales_channel_workspace_service ON sales_channel (workspace_id, service)');
        $this->addSql('CREATE INDEX IDX_AC00F22782D40A1F ON sales_channel (workspace_id)');
        $this->addSql('ALTER TABLE sales_channel ADD CONSTRAINT FK_AC00F22782D40A1F FOREIGN KEY (workspace_id) REFERENCES workspace (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('CREATE TABLE product_channel_price (id UUID NOT NULL, price_cents INT NOT NULL, product_id UUID NOT NULL, channel_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX product_channel_price_unique ON product_channel_price (product_id, channel_id)');
        $this->addSql('CREATE INDEX IDX_A70436B34584665A ON product_channel_price (product_id)');
        $this->addSql('CREATE INDEX IDX_A70436B372F5A1AA ON product_channel_price (channel_id)');
        $this->addSql('ALTER TABLE product_channel_price ADD CONSTRAINT FK_A70436B34584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE product_channel_price ADD CONSTRAINT FK_A70436B372F5A1AA FOREIGN KEY (channel_id) REFERENCES sales_channel (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE "order" ADD channel_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE "order" ADD CONSTRAINT FK_F529939872F5A1AA FOREIGN KEY (channel_id) REFERENCES sales_channel (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_F529939872F5A1AA ON "order" (channel_id)');

        $this->addSql('INSERT INTO sales_channel (id, name, service, kind, main, created_at, workspace_id) SELECT gen_random_uuid(), \'Marchés\', NULL, \'market\', true, NOW(), id FROM workspace');
        $this->addSql('UPDATE "order" o SET channel_id = c.id FROM sales_channel c WHERE c.workspace_id = o.workspace_id AND c.main AND o.event_id IS NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "order" DROP CONSTRAINT FK_F529939872F5A1AA');
        $this->addSql('DROP INDEX IDX_F529939872F5A1AA');
        $this->addSql('ALTER TABLE "order" DROP channel_id');
        $this->addSql('ALTER TABLE product_channel_price DROP CONSTRAINT FK_A70436B34584665A');
        $this->addSql('ALTER TABLE product_channel_price DROP CONSTRAINT FK_A70436B372F5A1AA');
        $this->addSql('ALTER TABLE sales_channel DROP CONSTRAINT FK_AC00F22782D40A1F');
        $this->addSql('DROP TABLE product_channel_price');
        $this->addSql('DROP TABLE sales_channel');
    }
}
