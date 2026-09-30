<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260930090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Product types get a colour, existing ones keep the colour they were shown with';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product_type ADD color VARCHAR(7) DEFAULT NULL');
        $this->addSql(<<<'SQL'
            UPDATE product_type SET color = ranked.color
            FROM (
                SELECT id, (ARRAY['#5b7f3a', '#b5654a', '#4f6d8f', '#c29a2e', '#8a6d8f', '#2f7f7a', '#7a5238', '#c07a8a'])[(ROW_NUMBER() OVER (PARTITION BY workspace_id ORDER BY LOWER(name)) - 1) % 8 + 1] AS color
                FROM product_type
            ) AS ranked
            WHERE product_type.id = ranked.id
            SQL);
        $this->addSql('ALTER TABLE product_type ALTER color SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product_type DROP color');
    }
}
