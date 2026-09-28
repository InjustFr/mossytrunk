<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260928063356 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE "order" (id UUID NOT NULL, reference VARCHAR(64) NOT NULL, placed_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, applied_discounts JSON NOT NULL, source VARCHAR(16) NOT NULL, sum_up_transaction_code VARCHAR(64) DEFAULT NULL, event_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F5299398AEA34913 ON "order" (reference)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F5299398BABA2D84 ON "order" (sum_up_transaction_code)');
        $this->addSql('CREATE INDEX order_placed_at_idx ON "order" (placed_at)');
        $this->addSql('CREATE INDEX IDX_F529939871F7E88B ON "order" (event_id)');
        $this->addSql('CREATE TABLE order_line (id UUID NOT NULL, product_id UUID NOT NULL, variant VARCHAR(255) DEFAULT NULL, product_name VARCHAR(255) NOT NULL, quantity INT NOT NULL, unit_price_cents INT NOT NULL, unit_cost_cents INT NOT NULL, order_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_9CE58EE18D9F6D38 ON order_line (order_id)');
        $this->addSql('ALTER TABLE "order" ADD CONSTRAINT FK_F529939871F7E88B FOREIGN KEY (event_id) REFERENCES event (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE order_line ADD CONSTRAINT FK_9CE58EE18D9F6D38 FOREIGN KEY (order_id) REFERENCES "order" (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE "order" DROP CONSTRAINT FK_F529939871F7E88B');
        $this->addSql('ALTER TABLE order_line DROP CONSTRAINT FK_9CE58EE18D9F6D38');
        $this->addSql('DROP TABLE "order"');
        $this->addSql('DROP TABLE order_line');
    }
}
