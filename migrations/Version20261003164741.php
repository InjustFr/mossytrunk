<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261003164741 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'The sales notebook reading is dropped';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE notebook_scan');
        $this->addSql('DROP TABLE notebook_template');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE notebook_scan (id UUID NOT NULL, scanned_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, pages INT NOT NULL, entries JSON NOT NULL, workspace_id UUID NOT NULL, event_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX notebook_scan_event ON notebook_scan (event_id)');
        $this->addSql('CREATE INDEX idx_e3b5615882d40a1f ON notebook_scan (workspace_id)');
        $this->addSql('CREATE TABLE notebook_template (id UUID NOT NULL, separation VARCHAR(16) NOT NULL, abbreviations JSON NOT NULL, workspace_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX notebook_template_workspace ON notebook_template (workspace_id)');
        $this->addSql('ALTER TABLE notebook_scan ADD CONSTRAINT fk_e3b5615882d40a1f FOREIGN KEY (workspace_id) REFERENCES workspace (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE notebook_scan ADD CONSTRAINT fk_e3b5615871f7e88b FOREIGN KEY (event_id) REFERENCES event (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE notebook_template ADD CONSTRAINT fk_4930a54b82d40a1f FOREIGN KEY (workspace_id) REFERENCES workspace (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
