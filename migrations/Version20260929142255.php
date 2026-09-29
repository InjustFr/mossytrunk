<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260929142255 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Etsy: app keys and shop connection per workspace, orders without event (Etsy receipt id, shipping charged), remembered Etsy listings';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE etsy_listing (id UUID NOT NULL, listing_key VARCHAR(300) NOT NULL, listing_id VARCHAR(32) NOT NULL, title VARCHAR(255) NOT NULL, variation VARCHAR(255) DEFAULT NULL, product_id UUID DEFAULT NULL, variant VARCHAR(255) DEFAULT NULL, seen_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, workspace_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX etsy_listing_workspace_key ON etsy_listing (workspace_id, listing_key)');
        $this->addSql('CREATE INDEX IDX_1ECBC1D382D40A1F ON etsy_listing (workspace_id)');
        $this->addSql('ALTER TABLE etsy_listing ADD CONSTRAINT FK_1ECBC1D382D40A1F FOREIGN KEY (workspace_id) REFERENCES workspace (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE "order" ADD etsy_receipt_id VARCHAR(32) DEFAULT NULL');
        $this->addSql('ALTER TABLE "order" ADD shipping_cents INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE "order" ALTER shipping_cents DROP DEFAULT');
        $this->addSql('ALTER TABLE "order" ALTER event_id DROP NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX order_workspace_etsy_receipt_id ON "order" (workspace_id, etsy_receipt_id)');
        $this->addSql('ALTER TABLE workspace ADD etsy_keystring VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE workspace ADD etsy_shop_id VARCHAR(32) DEFAULT NULL');
        $this->addSql('ALTER TABLE workspace ADD etsy_shop_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE workspace ADD etsy_token_expires_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE etsy_listing DROP CONSTRAINT FK_1ECBC1D382D40A1F');
        $this->addSql('DROP TABLE etsy_listing');
        $this->addSql('DROP INDEX order_workspace_etsy_receipt_id');
        $this->addSql('ALTER TABLE "order" DROP etsy_receipt_id');
        $this->addSql('ALTER TABLE "order" DROP shipping_cents');
        $this->addSql('DELETE FROM "order" WHERE event_id IS NULL');
        $this->addSql('ALTER TABLE "order" ALTER event_id SET NOT NULL');
        $this->addSql('ALTER TABLE workspace DROP etsy_keystring');
        $this->addSql('ALTER TABLE workspace DROP etsy_shop_id');
        $this->addSql('ALTER TABLE workspace DROP etsy_shop_name');
        $this->addSql('ALTER TABLE workspace DROP etsy_token_expires_at');
    }
}
