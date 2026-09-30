<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260930120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Users may choose the language of the app';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_user ADD language VARCHAR(2) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_user DROP language');
    }
}
