<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261002191510 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Connected services have fees per payment method';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE service_payment_fee (id UUID NOT NULL, payment_method VARCHAR(16) NOT NULL, label VARCHAR(100) NOT NULL, kind VARCHAR(16) NOT NULL, amount INT NOT NULL, connection_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_6DB0832DDD03F01 ON service_payment_fee (connection_id)');
        $this->addSql('ALTER TABLE service_payment_fee ADD CONSTRAINT FK_6DB0832DDD03F01 FOREIGN KEY (connection_id) REFERENCES service_connection (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE service_payment_fee DROP CONSTRAINT FK_6DB0832DDD03F01');
        $this->addSql('DROP TABLE service_payment_fee');
    }
}
