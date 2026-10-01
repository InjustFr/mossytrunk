<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261001160235 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Reference formats chosen per workspace and kind of item';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE reference_format (id UUID NOT NULL, kind VARCHAR(32) NOT NULL, template VARCHAR(255) NOT NULL, next_number INT NOT NULL, workspace_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX reference_format_workspace_kind ON reference_format (workspace_id, kind)');
        $this->addSql('CREATE INDEX IDX_C3DBC3DB82D40A1F ON reference_format (workspace_id)');
        $this->addSql('ALTER TABLE reference_format ADD CONSTRAINT FK_C3DBC3DB82D40A1F FOREIGN KEY (workspace_id) REFERENCES workspace (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reference_format DROP CONSTRAINT FK_C3DBC3DB82D40A1F');
        $this->addSql('DROP TABLE reference_format');
    }
}
