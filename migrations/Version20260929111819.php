<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260929111819 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Designs, design collections and gabarits; declinations become products on validation';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE design (id UUID NOT NULL, name VARCHAR(255) NOT NULL, notes TEXT DEFAULT NULL, status VARCHAR(16) NOT NULL, current BOOLEAN NOT NULL, validated_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, workspace_id UUID NOT NULL, collection_id UUID DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_CD4F5A3082D40A1F ON design (workspace_id)');
        $this->addSql('CREATE INDEX IDX_CD4F5A30514956FD ON design (collection_id)');
        $this->addSql('CREATE TABLE design_collection (id UUID NOT NULL, name VARCHAR(255) NOT NULL, description TEXT DEFAULT NULL, current BOOLEAN NOT NULL, workspace_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_F9D256E782D40A1F ON design_collection (workspace_id)');
        $this->addSql('CREATE TABLE design_declination (id UUID NOT NULL, product_name VARCHAR(255) NOT NULL, variants JSON NOT NULL, adaptations JSON NOT NULL, done_adaptations JSON NOT NULL, product_id UUID DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, selling_price_cents INT NOT NULL, buying_price_cents INT NOT NULL, design_id UUID NOT NULL, gabarit_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_CBE4EF2DE41DC9B2 ON design_declination (design_id)');
        $this->addSql('CREATE INDEX IDX_CBE4EF2D8B339010 ON design_declination (gabarit_id)');
        $this->addSql('CREATE TABLE gabarit (id UUID NOT NULL, name VARCHAR(255) NOT NULL, variants JSON NOT NULL, adaptations JSON NOT NULL, selling_price_cents INT NOT NULL, buying_price_cents INT NOT NULL, workspace_id UUID NOT NULL, type_id UUID DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX gabarit_workspace_name ON gabarit (workspace_id, name)');
        $this->addSql('CREATE INDEX IDX_C49F1C5482D40A1F ON gabarit (workspace_id)');
        $this->addSql('CREATE INDEX IDX_C49F1C54C54C8C93 ON gabarit (type_id)');
        $this->addSql('ALTER TABLE design ADD CONSTRAINT FK_CD4F5A3082D40A1F FOREIGN KEY (workspace_id) REFERENCES workspace (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE design ADD CONSTRAINT FK_CD4F5A30514956FD FOREIGN KEY (collection_id) REFERENCES design_collection (id) ON DELETE SET NULL NOT DEFERRABLE');
        $this->addSql('ALTER TABLE design_collection ADD CONSTRAINT FK_F9D256E782D40A1F FOREIGN KEY (workspace_id) REFERENCES workspace (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE design_declination ADD CONSTRAINT FK_CBE4EF2DE41DC9B2 FOREIGN KEY (design_id) REFERENCES design (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE design_declination ADD CONSTRAINT FK_CBE4EF2D8B339010 FOREIGN KEY (gabarit_id) REFERENCES gabarit (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE gabarit ADD CONSTRAINT FK_C49F1C5482D40A1F FOREIGN KEY (workspace_id) REFERENCES workspace (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE gabarit ADD CONSTRAINT FK_C49F1C54C54C8C93 FOREIGN KEY (type_id) REFERENCES product_type (id) ON DELETE SET NULL NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE design DROP CONSTRAINT FK_CD4F5A3082D40A1F');
        $this->addSql('ALTER TABLE design DROP CONSTRAINT FK_CD4F5A30514956FD');
        $this->addSql('ALTER TABLE design_collection DROP CONSTRAINT FK_F9D256E782D40A1F');
        $this->addSql('ALTER TABLE design_declination DROP CONSTRAINT FK_CBE4EF2DE41DC9B2');
        $this->addSql('ALTER TABLE design_declination DROP CONSTRAINT FK_CBE4EF2D8B339010');
        $this->addSql('ALTER TABLE gabarit DROP CONSTRAINT FK_C49F1C5482D40A1F');
        $this->addSql('ALTER TABLE gabarit DROP CONSTRAINT FK_C49F1C54C54C8C93');
        $this->addSql('DROP TABLE design');
        $this->addSql('DROP TABLE design_collection');
        $this->addSql('DROP TABLE design_declination');
        $this->addSql('DROP TABLE gabarit');
    }
}
