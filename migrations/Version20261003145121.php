<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261003145121 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Notebook templates: how each workspace writes its sales notebook';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE notebook_template (id UUID NOT NULL, separation VARCHAR(16) NOT NULL, abbreviations JSON NOT NULL, workspace_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX notebook_template_workspace ON notebook_template (workspace_id)');
        $this->addSql('ALTER TABLE notebook_template ADD CONSTRAINT FK_4930A54B82D40A1F FOREIGN KEY (workspace_id) REFERENCES workspace (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE notebook_template DROP CONSTRAINT FK_4930A54B82D40A1F');
        $this->addSql('DROP TABLE notebook_template');
    }
}
