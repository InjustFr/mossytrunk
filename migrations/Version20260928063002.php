<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260928063002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE discount_rule (id UUID NOT NULL, name VARCHAR(255) NOT NULL, bundle_size INT NOT NULL, active BOOLEAN NOT NULL, bundle_price_cents INT NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE discount_rule_product (discount_rule_id UUID NOT NULL, product_id UUID NOT NULL, PRIMARY KEY (discount_rule_id, product_id))');
        $this->addSql('CREATE INDEX IDX_B9EF473C2466E40 ON discount_rule_product (discount_rule_id)');
        $this->addSql('CREATE INDEX IDX_B9EF4734584665A ON discount_rule_product (product_id)');
        $this->addSql('ALTER TABLE discount_rule_product ADD CONSTRAINT FK_B9EF473C2466E40 FOREIGN KEY (discount_rule_id) REFERENCES discount_rule (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE discount_rule_product ADD CONSTRAINT FK_B9EF4734584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE discount_rule_product DROP CONSTRAINT FK_B9EF473C2466E40');
        $this->addSql('ALTER TABLE discount_rule_product DROP CONSTRAINT FK_B9EF4734584665A');
        $this->addSql('DROP TABLE discount_rule');
        $this->addSql('DROP TABLE discount_rule_product');
    }
}
