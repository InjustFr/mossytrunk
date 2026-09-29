<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260929104404 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Stock per (product, variant) in FIFO lots, low stock threshold, stock checks after events; order lines keep their total cost';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE stock_check (id UUID NOT NULL, checked_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, workspace_id UUID NOT NULL, event_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_A09EB0E282D40A1F ON stock_check (workspace_id)');
        $this->addSql('CREATE INDEX IDX_A09EB0E271F7E88B ON stock_check (event_id)');
        $this->addSql('CREATE TABLE stock_check_line (id UUID NOT NULL, product_id UUID NOT NULL, variant VARCHAR(255) DEFAULT NULL, label VARCHAR(255) NOT NULL, expected INT NOT NULL, counted INT NOT NULL, unexplained INT NOT NULL, dismissed BOOLEAN NOT NULL, loss_cost_cents INT NOT NULL, check_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_9ADD37DB709385E7 ON stock_check_line (check_id)');
        $this->addSql('CREATE TABLE stock_item (id UUID NOT NULL, variant VARCHAR(255) DEFAULT NULL, on_hand INT NOT NULL, last_unit_cost_cents INT DEFAULT NULL, workspace_id UUID NOT NULL, product_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX stock_item_product_idx ON stock_item (product_id)');
        $this->addSql('CREATE INDEX IDX_6017DDA82D40A1F ON stock_item (workspace_id)');
        $this->addSql('CREATE TABLE stock_lot (id UUID NOT NULL, quantity INT NOT NULL, remaining INT NOT NULL, received_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, origin VARCHAR(16) NOT NULL, source_id UUID DEFAULT NULL, total_cost_cents INT NOT NULL, item_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_7DEF9C8D126F525E ON stock_lot (item_id)');
        $this->addSql('ALTER TABLE stock_check ADD CONSTRAINT FK_A09EB0E282D40A1F FOREIGN KEY (workspace_id) REFERENCES workspace (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE stock_check ADD CONSTRAINT FK_A09EB0E271F7E88B FOREIGN KEY (event_id) REFERENCES event (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE stock_check_line ADD CONSTRAINT FK_9ADD37DB709385E7 FOREIGN KEY (check_id) REFERENCES stock_check (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE stock_item ADD CONSTRAINT FK_6017DDA82D40A1F FOREIGN KEY (workspace_id) REFERENCES workspace (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE stock_item ADD CONSTRAINT FK_6017DDA4584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE stock_lot ADD CONSTRAINT FK_7DEF9C8D126F525E FOREIGN KEY (item_id) REFERENCES stock_item (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE order_line RENAME COLUMN unit_cost_cents TO cost_cents');
        $this->addSql('UPDATE order_line SET cost_cents = cost_cents * quantity');
        $this->addSql('ALTER TABLE product ADD low_stock_threshold INT DEFAULT 10 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE stock_check DROP CONSTRAINT FK_A09EB0E282D40A1F');
        $this->addSql('ALTER TABLE stock_check DROP CONSTRAINT FK_A09EB0E271F7E88B');
        $this->addSql('ALTER TABLE stock_check_line DROP CONSTRAINT FK_9ADD37DB709385E7');
        $this->addSql('ALTER TABLE stock_item DROP CONSTRAINT FK_6017DDA82D40A1F');
        $this->addSql('ALTER TABLE stock_item DROP CONSTRAINT FK_6017DDA4584665A');
        $this->addSql('ALTER TABLE stock_lot DROP CONSTRAINT FK_7DEF9C8D126F525E');
        $this->addSql('DROP TABLE stock_check');
        $this->addSql('DROP TABLE stock_check_line');
        $this->addSql('DROP TABLE stock_item');
        $this->addSql('DROP TABLE stock_lot');
        $this->addSql('UPDATE order_line SET cost_cents = ROUND(cost_cents::numeric / quantity)');
        $this->addSql('ALTER TABLE order_line RENAME COLUMN cost_cents TO unit_cost_cents');
        $this->addSql('ALTER TABLE product DROP low_stock_threshold');
    }
}
