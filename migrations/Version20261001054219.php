<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261001054219 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Orders can be refunded';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "order" ADD refunded_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "order" DROP refunded_at');
    }
}
