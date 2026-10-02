<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261002061842 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Sales channels have per-order costs; orders keep their channel charges and postage';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE sales_channel_cost (id UUID NOT NULL, label VARCHAR(100) NOT NULL, kind VARCHAR(16) NOT NULL, amount INT NOT NULL, channel_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_183F3A3A72F5A1AA ON sales_channel_cost (channel_id)');
        $this->addSql('ALTER TABLE sales_channel_cost ADD CONSTRAINT FK_183F3A3A72F5A1AA FOREIGN KEY (channel_id) REFERENCES sales_channel (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE "order" ADD channel_charges JSON DEFAULT \'[]\' NOT NULL');
        $this->addSql('ALTER TABLE "order" ADD postage_cents INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE "order" ALTER postage_cents DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE sales_channel_cost DROP CONSTRAINT FK_183F3A3A72F5A1AA');
        $this->addSql('DROP TABLE sales_channel_cost');
        $this->addSql('ALTER TABLE "order" DROP channel_charges');
        $this->addSql('ALTER TABLE "order" DROP postage_cents');
    }
}
