<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260928170502 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Encrypted workspace secrets and SumUp merchant code';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE workspace_secret (id UUID NOT NULL, name VARCHAR(50) NOT NULL, ciphertext TEXT NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, workspace_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX workspace_secret_workspace_name ON workspace_secret (workspace_id, name)');
        $this->addSql('CREATE INDEX IDX_6C62394D82D40A1F ON workspace_secret (workspace_id)');
        $this->addSql('ALTER TABLE workspace_secret ADD CONSTRAINT FK_6C62394D82D40A1F FOREIGN KEY (workspace_id) REFERENCES workspace (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE workspace ADD sum_up_merchant_code VARCHAR(32) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE workspace_secret DROP CONSTRAINT FK_6C62394D82D40A1F');
        $this->addSql('DROP TABLE workspace_secret');
        $this->addSql('ALTER TABLE workspace DROP sum_up_merchant_code');
    }
}
