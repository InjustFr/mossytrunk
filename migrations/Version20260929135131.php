<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260929135131 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'URSSAF declarations recorded per period, and the workspace declaration periodicity (monthly by default)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE urssaf_declaration (id UUID NOT NULL, period VARCHAR(16) NOT NULL, declared_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, turnover_cents INT NOT NULL, workspace_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX urssaf_declaration_workspace_period ON urssaf_declaration (workspace_id, period)');
        $this->addSql('CREATE INDEX IDX_FB8116E782D40A1F ON urssaf_declaration (workspace_id)');
        $this->addSql('ALTER TABLE urssaf_declaration ADD CONSTRAINT FK_FB8116E782D40A1F FOREIGN KEY (workspace_id) REFERENCES workspace (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE workspace ADD declaration_periodicity VARCHAR(16) DEFAULT \'monthly\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE urssaf_declaration DROP CONSTRAINT FK_FB8116E782D40A1F');
        $this->addSql('DROP TABLE urssaf_declaration');
        $this->addSql('ALTER TABLE workspace DROP declaration_periodicity');
    }
}
