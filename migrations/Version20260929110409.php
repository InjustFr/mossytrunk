<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260929110409 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Suppliers and supplier orders (ordered, then received into stock)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE supplier (id UUID NOT NULL, name VARCHAR(255) NOT NULL, contact VARCHAR(255) DEFAULT NULL, notes TEXT DEFAULT NULL, workspace_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX supplier_workspace_name ON supplier (workspace_id, name)');
        $this->addSql('CREATE INDEX IDX_9B2A6C7E82D40A1F ON supplier (workspace_id)');
        $this->addSql('CREATE TABLE supplier_order (id UUID NOT NULL, reference VARCHAR(64) NOT NULL, ordered_on DATE NOT NULL, status VARCHAR(16) NOT NULL, received_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL, workspace_id UUID NOT NULL, supplier_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX supplier_order_workspace_reference ON supplier_order (workspace_id, reference)');
        $this->addSql('CREATE INDEX IDX_2C3291B282D40A1F ON supplier_order (workspace_id)');
        $this->addSql('CREATE INDEX IDX_2C3291B22ADD6D8C ON supplier_order (supplier_id)');
        $this->addSql('CREATE TABLE supplier_order_line (id UUID NOT NULL, product_id UUID NOT NULL, variant VARCHAR(255) DEFAULT NULL, label VARCHAR(255) NOT NULL, ordered_quantity INT NOT NULL, received_quantity INT DEFAULT NULL, position INT NOT NULL, total_price_cents INT NOT NULL, order_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_FD1443088D9F6D38 ON supplier_order_line (order_id)');
        $this->addSql('ALTER TABLE supplier ADD CONSTRAINT FK_9B2A6C7E82D40A1F FOREIGN KEY (workspace_id) REFERENCES workspace (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE supplier_order ADD CONSTRAINT FK_2C3291B282D40A1F FOREIGN KEY (workspace_id) REFERENCES workspace (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE supplier_order ADD CONSTRAINT FK_2C3291B22ADD6D8C FOREIGN KEY (supplier_id) REFERENCES supplier (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE supplier_order_line ADD CONSTRAINT FK_FD1443088D9F6D38 FOREIGN KEY (order_id) REFERENCES supplier_order (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE supplier DROP CONSTRAINT FK_9B2A6C7E82D40A1F');
        $this->addSql('ALTER TABLE supplier_order DROP CONSTRAINT FK_2C3291B282D40A1F');
        $this->addSql('ALTER TABLE supplier_order DROP CONSTRAINT FK_2C3291B22ADD6D8C');
        $this->addSql('ALTER TABLE supplier_order_line DROP CONSTRAINT FK_FD1443088D9F6D38');
        $this->addSql('DROP TABLE supplier');
        $this->addSql('DROP TABLE supplier_order');
        $this->addSql('DROP TABLE supplier_order_line');
    }
}
