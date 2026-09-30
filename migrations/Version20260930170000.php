<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260930170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Imported orders get an internal reference; the sales they come from are kept apart, so one order can gather several of them';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE order_imported_sale (id UUID NOT NULL, source VARCHAR(32) NOT NULL, external_id VARCHAR(64) NOT NULL, reference VARCHAR(64) NOT NULL, payment_method VARCHAR(16) DEFAULT NULL, order_id UUID NOT NULL, workspace_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX imported_sale_workspace_source_external_id ON order_imported_sale (workspace_id, source, external_id)');
        $this->addSql('CREATE INDEX IDX_81AAA33A8D9F6D38 ON order_imported_sale (order_id)');
        $this->addSql('CREATE INDEX IDX_81AAA33A82D40A1F ON order_imported_sale (workspace_id)');
        $this->addSql('ALTER TABLE order_imported_sale ADD CONSTRAINT FK_81AAA33A8D9F6D38 FOREIGN KEY (order_id) REFERENCES "order" (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE order_imported_sale ADD CONSTRAINT FK_81AAA33A82D40A1F FOREIGN KEY (workspace_id) REFERENCES workspace (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('INSERT INTO order_imported_sale (id, source, external_id, reference, payment_method, order_id, workspace_id) SELECT gen_random_uuid(), source, external_id, reference, payment_method, id, workspace_id FROM "order" WHERE external_id IS NOT NULL');
        $this->addSql("UPDATE \"order\" SET reference = 'CMD-' || to_char(placed_at AT TIME ZONE 'Europe/Paris', 'YYYYMMDD') || '-' || upper(substr(md5(id::text), 1, 6)) WHERE external_id IS NOT NULL");
        $this->addSql('DROP INDEX order_workspace_source_external_id');
        $this->addSql('ALTER TABLE "order" DROP external_id');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "order" ADD external_id VARCHAR(64) DEFAULT NULL');
        $this->addSql('UPDATE "order" o SET external_id = s.external_id, reference = s.reference FROM (SELECT DISTINCT ON (order_id) order_id, external_id, reference FROM order_imported_sale ORDER BY order_id, external_id) s WHERE s.order_id = o.id');
        $this->addSql('CREATE UNIQUE INDEX order_workspace_source_external_id ON "order" (workspace_id, source, external_id)');
        $this->addSql('DROP TABLE order_imported_sale');
    }
}
