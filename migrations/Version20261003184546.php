<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261003184546 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Users may choose the background and accent colours of the app';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_user ADD theme_background VARCHAR(7) DEFAULT NULL');
        $this->addSql('ALTER TABLE app_user ADD theme_accent VARCHAR(7) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE app_user DROP theme_background');
        $this->addSql('ALTER TABLE app_user DROP theme_accent');
    }
}
