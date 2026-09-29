<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260929061401 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Discount rules made of conditions (product or type quantities), an action and a validity period (existing rules are dropped)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DELETE FROM discount_rule');
        $this->addSql('CREATE TABLE discount_condition (id UUID NOT NULL, quantity INT NOT NULL, rule_id UUID NOT NULL, kind VARCHAR(16) NOT NULL, product_id UUID DEFAULT NULL, type_id UUID DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_DD0C00BE744E0351 ON discount_condition (rule_id)');
        $this->addSql('CREATE INDEX IDX_DD0C00BE4584665A ON discount_condition (product_id)');
        $this->addSql('CREATE INDEX IDX_DD0C00BEC54C8C93 ON discount_condition (type_id)');
        $this->addSql('ALTER TABLE discount_condition ADD CONSTRAINT FK_DD0C00BE744E0351 FOREIGN KEY (rule_id) REFERENCES discount_rule (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE discount_condition ADD CONSTRAINT FK_DD0C00BE4584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE discount_condition ADD CONSTRAINT FK_DD0C00BEC54C8C93 FOREIGN KEY (type_id) REFERENCES product_type (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE discount_rule_product DROP CONSTRAINT fk_b9ef4734584665a');
        $this->addSql('ALTER TABLE discount_rule_product DROP CONSTRAINT fk_b9ef473c2466e40');
        $this->addSql('ALTER TABLE discount_rule_product_type DROP CONSTRAINT fk_72f470ecc2466e40');
        $this->addSql('ALTER TABLE discount_rule_product_type DROP CONSTRAINT fk_72f470ec14959723');
        $this->addSql('DROP TABLE discount_rule_product');
        $this->addSql('DROP TABLE discount_rule_product_type');
        $this->addSql('ALTER TABLE discount_rule ADD action_kind VARCHAR(16) NOT NULL');
        $this->addSql('ALTER TABLE discount_rule ADD action_value INT NOT NULL');
        $this->addSql('ALTER TABLE discount_rule ADD valid_start DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE discount_rule ADD valid_end DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE discount_rule DROP bundle_size');
        $this->addSql('ALTER TABLE discount_rule DROP bundle_price_cents');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM discount_rule');
        $this->addSql('CREATE TABLE discount_rule_product (discount_rule_id UUID NOT NULL, product_id UUID NOT NULL, PRIMARY KEY (discount_rule_id, product_id))');
        $this->addSql('CREATE INDEX idx_b9ef4734584665a ON discount_rule_product (product_id)');
        $this->addSql('CREATE INDEX idx_b9ef473c2466e40 ON discount_rule_product (discount_rule_id)');
        $this->addSql('CREATE TABLE discount_rule_product_type (discount_rule_id UUID NOT NULL, product_type_id UUID NOT NULL, PRIMARY KEY (discount_rule_id, product_type_id))');
        $this->addSql('CREATE INDEX idx_72f470ec14959723 ON discount_rule_product_type (product_type_id)');
        $this->addSql('CREATE INDEX idx_72f470ecc2466e40 ON discount_rule_product_type (discount_rule_id)');
        $this->addSql('ALTER TABLE discount_rule_product ADD CONSTRAINT fk_b9ef4734584665a FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE discount_rule_product ADD CONSTRAINT fk_b9ef473c2466e40 FOREIGN KEY (discount_rule_id) REFERENCES discount_rule (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE discount_rule_product_type ADD CONSTRAINT fk_72f470ecc2466e40 FOREIGN KEY (discount_rule_id) REFERENCES discount_rule (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE discount_rule_product_type ADD CONSTRAINT fk_72f470ec14959723 FOREIGN KEY (product_type_id) REFERENCES product_type (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE discount_condition DROP CONSTRAINT FK_DD0C00BE744E0351');
        $this->addSql('ALTER TABLE discount_condition DROP CONSTRAINT FK_DD0C00BE4584665A');
        $this->addSql('ALTER TABLE discount_condition DROP CONSTRAINT FK_DD0C00BEC54C8C93');
        $this->addSql('DROP TABLE discount_condition');
        $this->addSql('ALTER TABLE discount_rule ADD bundle_price_cents INT NOT NULL');
        $this->addSql('ALTER TABLE discount_rule DROP action_kind');
        $this->addSql('ALTER TABLE discount_rule DROP valid_start');
        $this->addSql('ALTER TABLE discount_rule DROP valid_end');
        $this->addSql('ALTER TABLE discount_rule RENAME COLUMN action_value TO bundle_size');
    }
}
