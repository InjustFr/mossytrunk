<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261001055807 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Index product references of order lines, stock check lines, declinations and external items';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX design_declination_product_idx ON design_declination (product_id)');
        $this->addSql('CREATE INDEX external_item_workspace_product_idx ON external_item (workspace_id, product_id)');
        $this->addSql('CREATE INDEX order_line_product_idx ON order_line (product_id)');
        $this->addSql('CREATE INDEX stock_check_line_product_idx ON stock_check_line (product_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX design_declination_product_idx');
        $this->addSql('DROP INDEX external_item_workspace_product_idx');
        $this->addSql('DROP INDEX order_line_product_idx');
        $this->addSql('DROP INDEX stock_check_line_product_idx');
    }
}
