<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260928165457 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Scope business data to a workspace (existing mock data is wiped)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('TRUNCATE order_line, "order", event_expense, event, discount_rule_product, discount_rule_product_type, discount_rule, product, product_type');
        $this->addSql('ALTER TABLE discount_rule ADD workspace_id UUID NOT NULL');
        $this->addSql('ALTER TABLE discount_rule ADD CONSTRAINT FK_D85AA97682D40A1F FOREIGN KEY (workspace_id) REFERENCES workspace (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_D85AA97682D40A1F ON discount_rule (workspace_id)');
        $this->addSql('ALTER TABLE event ADD workspace_id UUID NOT NULL');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA782D40A1F FOREIGN KEY (workspace_id) REFERENCES workspace (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_3BAE0AA782D40A1F ON event (workspace_id)');
        $this->addSql('DROP INDEX order_placed_at_idx');
        $this->addSql('DROP INDEX uniq_f5299398baba2d84');
        $this->addSql('ALTER TABLE "order" ADD workspace_id UUID NOT NULL');
        $this->addSql('ALTER TABLE "order" ADD CONSTRAINT FK_F529939882D40A1F FOREIGN KEY (workspace_id) REFERENCES workspace (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('CREATE INDEX order_workspace_placed_at_idx ON "order" (workspace_id, placed_at)');
        $this->addSql('CREATE UNIQUE INDEX order_workspace_sum_up_transaction_code ON "order" (workspace_id, sum_up_transaction_code)');
        $this->addSql('CREATE INDEX IDX_F529939882D40A1F ON "order" (workspace_id)');
        $this->addSql('DROP INDEX uniq_d34a04adaea34913');
        $this->addSql('ALTER TABLE product ADD workspace_id UUID NOT NULL');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD82D40A1F FOREIGN KEY (workspace_id) REFERENCES workspace (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('CREATE UNIQUE INDEX product_workspace_reference ON product (workspace_id, reference)');
        $this->addSql('CREATE INDEX IDX_D34A04AD82D40A1F ON product (workspace_id)');
        $this->addSql('DROP INDEX uniq_136758877153098');
        $this->addSql('DROP INDEX uniq_13675885e237e06');
        $this->addSql('ALTER TABLE product_type ADD workspace_id UUID NOT NULL');
        $this->addSql('ALTER TABLE product_type ADD CONSTRAINT FK_136758882D40A1F FOREIGN KEY (workspace_id) REFERENCES workspace (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('CREATE UNIQUE INDEX product_type_workspace_name ON product_type (workspace_id, name)');
        $this->addSql('CREATE UNIQUE INDEX product_type_workspace_code ON product_type (workspace_id, code)');
        $this->addSql('CREATE INDEX IDX_136758882D40A1F ON product_type (workspace_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE discount_rule DROP CONSTRAINT FK_D85AA97682D40A1F');
        $this->addSql('DROP INDEX IDX_D85AA97682D40A1F');
        $this->addSql('ALTER TABLE discount_rule DROP workspace_id');
        $this->addSql('ALTER TABLE event DROP CONSTRAINT FK_3BAE0AA782D40A1F');
        $this->addSql('DROP INDEX IDX_3BAE0AA782D40A1F');
        $this->addSql('ALTER TABLE event DROP workspace_id');
        $this->addSql('ALTER TABLE "order" DROP CONSTRAINT FK_F529939882D40A1F');
        $this->addSql('DROP INDEX order_workspace_placed_at_idx');
        $this->addSql('DROP INDEX order_workspace_sum_up_transaction_code');
        $this->addSql('DROP INDEX IDX_F529939882D40A1F');
        $this->addSql('ALTER TABLE "order" DROP workspace_id');
        $this->addSql('CREATE INDEX order_placed_at_idx ON "order" (placed_at)');
        $this->addSql('CREATE UNIQUE INDEX uniq_f5299398baba2d84 ON "order" (sum_up_transaction_code)');
        $this->addSql('ALTER TABLE product DROP CONSTRAINT FK_D34A04AD82D40A1F');
        $this->addSql('DROP INDEX product_workspace_reference');
        $this->addSql('DROP INDEX IDX_D34A04AD82D40A1F');
        $this->addSql('ALTER TABLE product DROP workspace_id');
        $this->addSql('CREATE UNIQUE INDEX uniq_d34a04adaea34913 ON product (reference)');
        $this->addSql('ALTER TABLE product_type DROP CONSTRAINT FK_136758882D40A1F');
        $this->addSql('DROP INDEX product_type_workspace_name');
        $this->addSql('DROP INDEX product_type_workspace_code');
        $this->addSql('DROP INDEX IDX_136758882D40A1F');
        $this->addSql('ALTER TABLE product_type DROP workspace_id');
        $this->addSql('CREATE UNIQUE INDEX uniq_136758877153098 ON product_type (code)');
        $this->addSql('CREATE UNIQUE INDEX uniq_13675885e237e06 ON product_type (name)');
    }
}
