<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260928062524 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE event (id UUID NOT NULL, name VARCHAR(255) NOT NULL, location VARCHAR(255) NOT NULL, period_start DATE NOT NULL, period_end DATE NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE event_expense (id UUID NOT NULL, label VARCHAR(255) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, amount_cents INT NOT NULL, event_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_A86D240971F7E88B ON event_expense (event_id)');
        $this->addSql('ALTER TABLE event_expense ADD CONSTRAINT FK_A86D240971F7E88B FOREIGN KEY (event_id) REFERENCES event (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE event_expense DROP CONSTRAINT FK_A86D240971F7E88B');
        $this->addSql('DROP TABLE event');
        $this->addSql('DROP TABLE event_expense');
    }
}
