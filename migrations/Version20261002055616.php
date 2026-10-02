<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261002055616 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Products are articles or supplies';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product ADD kind VARCHAR(16) DEFAULT \'article\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product DROP kind');
    }
}
