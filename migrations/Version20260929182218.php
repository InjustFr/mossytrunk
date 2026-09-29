<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260929182218 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'External services: SumUp and Etsy settings become service connections, Etsy listings become external items, orders keep a generic source and external id, references unique per workspace';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE service_connection (id UUID NOT NULL, service VARCHAR(32) NOT NULL, settings JSON NOT NULL, sales_context VARCHAR(16) NOT NULL, unknown_items VARCHAR(16) NOT NULL, account_id VARCHAR(64) DEFAULT NULL, account_name VARCHAR(255) DEFAULT NULL, token_expires_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, workspace_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX service_connection_workspace_service ON service_connection (workspace_id, service)');
        $this->addSql('CREATE INDEX IDX_BFA64AD282D40A1F ON service_connection (workspace_id)');
        $this->addSql('ALTER TABLE service_connection ADD CONSTRAINT FK_BFA64AD282D40A1F FOREIGN KEY (workspace_id) REFERENCES workspace (id) ON DELETE CASCADE NOT DEFERRABLE');

        $this->addSql(<<<'SQL'
            INSERT INTO service_connection (id, workspace_id, service, settings, sales_context, unknown_items, created_at)
            SELECT gen_random_uuid(), w.id, 'sumup',
                   CASE WHEN w.sum_up_merchant_code IS NULL THEN '{}'::json ELSE json_build_object('merchant_code', w.sum_up_merchant_code) END,
                   'at_event', 'create_product', NOW()
            FROM workspace w
            WHERE w.sum_up_merchant_code IS NOT NULL
               OR EXISTS (SELECT 1 FROM workspace_secret s WHERE s.workspace_id = w.id AND s.name = 'sumup_api_key')
            SQL);
        $this->addSql(<<<'SQL'
            INSERT INTO service_connection (id, workspace_id, service, settings, sales_context, unknown_items, account_id, account_name, token_expires_at, created_at)
            SELECT gen_random_uuid(), w.id, 'etsy',
                   CASE WHEN w.etsy_keystring IS NULL THEN '{}'::json ELSE json_build_object('keystring', w.etsy_keystring) END,
                   'online', 'link_by_hand', w.etsy_shop_id, w.etsy_shop_name, w.etsy_token_expires_at, NOW()
            FROM workspace w
            WHERE w.etsy_keystring IS NOT NULL
               OR w.etsy_shop_id IS NOT NULL
               OR EXISTS (SELECT 1 FROM workspace_secret s WHERE s.workspace_id = w.id AND s.name LIKE 'etsy\_%')
            SQL);

        $this->addSql('CREATE TABLE external_item (id UUID NOT NULL, service VARCHAR(32) NOT NULL, item_key VARCHAR(300) NOT NULL, external_ref VARCHAR(255) NOT NULL, label VARCHAR(255) NOT NULL, variation VARCHAR(255) DEFAULT NULL, product_id UUID DEFAULT NULL, variant VARCHAR(255) DEFAULT NULL, seen_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, workspace_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX external_item_workspace_service_key ON external_item (workspace_id, service, item_key)');
        $this->addSql('CREATE INDEX IDX_8A04453282D40A1F ON external_item (workspace_id)');
        $this->addSql('ALTER TABLE external_item ADD CONSTRAINT FK_8A04453282D40A1F FOREIGN KEY (workspace_id) REFERENCES workspace (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql(<<<'SQL'
            INSERT INTO external_item (id, workspace_id, service, item_key, external_ref, label, variation, product_id, variant, seen_at)
            SELECT id, workspace_id, 'etsy', listing_key, listing_id, title, variation, product_id, variant, seen_at FROM etsy_listing
            SQL);
        $this->addSql('ALTER TABLE etsy_listing DROP CONSTRAINT fk_1ecbc1d382d40a1f');
        $this->addSql('DROP TABLE etsy_listing');

        $this->addSql('UPDATE "order" SET sum_up_transaction_code = etsy_receipt_id WHERE etsy_receipt_id IS NOT NULL');
        $this->addSql('DROP INDEX uniq_f5299398aea34913');
        $this->addSql('DROP INDEX order_workspace_sum_up_transaction_code');
        $this->addSql('DROP INDEX order_workspace_etsy_receipt_id');
        $this->addSql('ALTER TABLE "order" DROP etsy_receipt_id');
        $this->addSql('ALTER TABLE "order" ALTER source TYPE VARCHAR(32)');
        $this->addSql('ALTER TABLE "order" RENAME COLUMN sum_up_transaction_code TO external_id');
        $this->addSql('CREATE UNIQUE INDEX order_workspace_reference ON "order" (workspace_id, reference)');
        $this->addSql('CREATE UNIQUE INDEX order_workspace_source_external_id ON "order" (workspace_id, source, external_id)');

        $this->addSql('ALTER TABLE workspace DROP sum_up_merchant_code');
        $this->addSql('ALTER TABLE workspace DROP etsy_keystring');
        $this->addSql('ALTER TABLE workspace DROP etsy_shop_id');
        $this->addSql('ALTER TABLE workspace DROP etsy_shop_name');
        $this->addSql('ALTER TABLE workspace DROP etsy_token_expires_at');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workspace ADD sum_up_merchant_code VARCHAR(32) DEFAULT NULL');
        $this->addSql('ALTER TABLE workspace ADD etsy_keystring VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE workspace ADD etsy_shop_id VARCHAR(32) DEFAULT NULL');
        $this->addSql('ALTER TABLE workspace ADD etsy_shop_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE workspace ADD etsy_token_expires_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL');
        $this->addSql("UPDATE workspace w SET sum_up_merchant_code = c.settings->>'merchant_code' FROM service_connection c WHERE c.workspace_id = w.id AND c.service = 'sumup'");
        $this->addSql("UPDATE workspace w SET etsy_keystring = c.settings->>'keystring', etsy_shop_id = c.account_id, etsy_shop_name = c.account_name, etsy_token_expires_at = c.token_expires_at FROM service_connection c WHERE c.workspace_id = w.id AND c.service = 'etsy'");

        $this->addSql('DROP INDEX order_workspace_reference');
        $this->addSql('DROP INDEX order_workspace_source_external_id');
        $this->addSql('ALTER TABLE "order" RENAME COLUMN external_id TO sum_up_transaction_code');
        $this->addSql('ALTER TABLE "order" ADD etsy_receipt_id VARCHAR(32) DEFAULT NULL');
        $this->addSql("UPDATE \"order\" SET etsy_receipt_id = sum_up_transaction_code, sum_up_transaction_code = NULL WHERE source = 'etsy'");
        $this->addSql('ALTER TABLE "order" ALTER source TYPE VARCHAR(16)');
        $this->addSql('CREATE UNIQUE INDEX uniq_f5299398aea34913 ON "order" (reference)');
        $this->addSql('CREATE UNIQUE INDEX order_workspace_sum_up_transaction_code ON "order" (workspace_id, sum_up_transaction_code)');
        $this->addSql('CREATE UNIQUE INDEX order_workspace_etsy_receipt_id ON "order" (workspace_id, etsy_receipt_id)');

        $this->addSql('CREATE TABLE etsy_listing (id UUID NOT NULL, listing_key VARCHAR(300) NOT NULL, listing_id VARCHAR(32) NOT NULL, title VARCHAR(255) NOT NULL, variation VARCHAR(255) DEFAULT NULL, product_id UUID DEFAULT NULL, variant VARCHAR(255) DEFAULT NULL, seen_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, workspace_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX etsy_listing_workspace_key ON etsy_listing (workspace_id, listing_key)');
        $this->addSql('CREATE INDEX idx_1ecbc1d382d40a1f ON etsy_listing (workspace_id)');
        $this->addSql('ALTER TABLE etsy_listing ADD CONSTRAINT fk_1ecbc1d382d40a1f FOREIGN KEY (workspace_id) REFERENCES workspace (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql("INSERT INTO etsy_listing (id, workspace_id, listing_key, listing_id, title, variation, product_id, variant, seen_at) SELECT id, workspace_id, item_key, LEFT(external_ref, 32), label, variation, product_id, variant, seen_at FROM external_item WHERE service = 'etsy'");

        $this->addSql('ALTER TABLE external_item DROP CONSTRAINT FK_8A04453282D40A1F');
        $this->addSql('ALTER TABLE service_connection DROP CONSTRAINT FK_BFA64AD282D40A1F');
        $this->addSql('DROP TABLE external_item');
        $this->addSql('DROP TABLE service_connection');
    }
}
