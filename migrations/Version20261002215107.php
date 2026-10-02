<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261002215107 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Imported sales keep the fee the service reported';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE order_imported_sale ADD fee INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE order_imported_sale DROP fee');
    }
}
