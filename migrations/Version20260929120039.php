<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260929120039 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Gabarits and declinations no longer carry a buying price: products get theirs from stock purchases';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE design_declination DROP buying_price_cents');
        $this->addSql('ALTER TABLE gabarit DROP buying_price_cents');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE design_declination ADD buying_price_cents INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE gabarit ADD buying_price_cents INT DEFAULT 0 NOT NULL');
    }
}
