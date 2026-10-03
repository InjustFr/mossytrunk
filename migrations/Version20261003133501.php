<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261003133501 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Notebook scans: the sales read on the photos of an event\'s notebook';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE notebook_scan (id UUID NOT NULL, scanned_at TIMESTAMP(0) WITH TIME ZONE NOT NULL, pages INT NOT NULL, entries JSON NOT NULL, workspace_id UUID NOT NULL, event_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX notebook_scan_event ON notebook_scan (event_id)');
        $this->addSql('CREATE INDEX IDX_E3B5615882D40A1F ON notebook_scan (workspace_id)');
        $this->addSql('ALTER TABLE notebook_scan ADD CONSTRAINT FK_E3B5615882D40A1F FOREIGN KEY (workspace_id) REFERENCES workspace (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE notebook_scan ADD CONSTRAINT FK_E3B5615871F7E88B FOREIGN KEY (event_id) REFERENCES event (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE notebook_scan DROP CONSTRAINT FK_E3B5615882D40A1F');
        $this->addSql('ALTER TABLE notebook_scan DROP CONSTRAINT FK_E3B5615871F7E88B');
        $this->addSql('DROP TABLE notebook_scan');
    }
}
