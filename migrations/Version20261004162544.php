<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261004162544 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Share an event expense over several events or until a date';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE event_expense ADD shared_over_events INT DEFAULT NULL');
        $this->addSql('ALTER TABLE event_expense ADD shared_until DATE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE event_expense DROP shared_over_events');
        $this->addSql('ALTER TABLE event_expense DROP shared_until');
    }
}
