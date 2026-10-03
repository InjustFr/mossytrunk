<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261003113240 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Services no longer carry payment fees: the fee of each sale is imported with it';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE service_payment_fee DROP CONSTRAINT fk_6db0832ddd03f01');
        $this->addSql('DROP TABLE service_payment_fee');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE TABLE service_payment_fee (id UUID NOT NULL, payment_method VARCHAR(16) NOT NULL, label VARCHAR(100) NOT NULL, kind VARCHAR(16) NOT NULL, amount INT NOT NULL, connection_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX idx_6db0832ddd03f01 ON service_payment_fee (connection_id)');
        $this->addSql('ALTER TABLE service_payment_fee ADD CONSTRAINT fk_6db0832ddd03f01 FOREIGN KEY (connection_id) REFERENCES service_connection (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
