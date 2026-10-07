<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261007120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Sign in with mossyleaf accounts: users keep their data and are linked to their account by email on first sign-in';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE password_token');
        $this->addSql('ALTER TABLE app_user DROP COLUMN password_hash');
        $this->addSql('ALTER TABLE app_user ADD account_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_88BDF3E99B6B5FBA ON app_user (account_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_88BDF3E99B6B5FBA');
        $this->addSql('ALTER TABLE app_user DROP COLUMN account_id');
        $this->addSql('ALTER TABLE app_user ADD password_hash VARCHAR(255) DEFAULT NULL');
        $this->addSql('CREATE TABLE password_token (id UUID NOT NULL, selector VARCHAR(24) NOT NULL, verifier_hash VARCHAR(64) NOT NULL, purpose VARCHAR(20) NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, used_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, user_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_BEAB6C249692E25D ON password_token (selector)');
        $this->addSql('CREATE INDEX IDX_BEAB6C24A76ED395 ON password_token (user_id)');
        $this->addSql('ALTER TABLE password_token ADD CONSTRAINT FK_BEAB6C24A76ED395 FOREIGN KEY (user_id) REFERENCES app_user (id) ON DELETE CASCADE NOT DEFERRABLE');
    }
}
