<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260930160000 extends AbstractMigration
{
    private const string FREE_AMOUNT_PRODUCTS = "SELECT id FROM product WHERE LOWER(name) IN ('montant libre', 'free amount', 'custom amount') AND variants::text = '[]'";

    public function getDescription(): string
    {
        return 'Amounts typed on a card terminal are sold as an unknown product instead of a « Montant libre » catalogue product';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE order_line ALTER product_id DROP NOT NULL');
        $this->addSql("UPDATE order_line SET product_id = NULL, variant = NULL, product_name = 'Produit inconnu', cost_cents = 0 WHERE product_id IN (".self::FREE_AMOUNT_PRODUCTS.')');
        $this->addSql('UPDATE external_item SET product_id = NULL, variant = NULL WHERE product_id IN ('.self::FREE_AMOUNT_PRODUCTS.')');
        $this->addSql('DELETE FROM product WHERE id IN ('.self::FREE_AMOUNT_PRODUCTS.')');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Unknown-product order lines cannot be given back a product.');
    }
}
