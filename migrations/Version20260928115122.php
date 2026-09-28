<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260928115122 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE discount_rule_product_type (discount_rule_id UUID NOT NULL, product_type_id UUID NOT NULL, PRIMARY KEY (discount_rule_id, product_type_id))');
        $this->addSql('CREATE INDEX IDX_72F470ECC2466E40 ON discount_rule_product_type (discount_rule_id)');
        $this->addSql('CREATE INDEX IDX_72F470EC14959723 ON discount_rule_product_type (product_type_id)');
        $this->addSql('ALTER TABLE discount_rule_product_type ADD CONSTRAINT FK_72F470ECC2466E40 FOREIGN KEY (discount_rule_id) REFERENCES discount_rule (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE discount_rule_product_type ADD CONSTRAINT FK_72F470EC14959723 FOREIGN KEY (product_type_id) REFERENCES product_type (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE discount_rule_product_type DROP CONSTRAINT FK_72F470ECC2466E40');
        $this->addSql('ALTER TABLE discount_rule_product_type DROP CONSTRAINT FK_72F470EC14959723');
        $this->addSql('DROP TABLE discount_rule_product_type');
    }
}
