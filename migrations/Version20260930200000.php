<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260930200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'A discount condition picks its units among a group of products and types: its target moves to discount_condition_target';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE discount_condition_target (id UUID NOT NULL, variant VARCHAR(100) DEFAULT NULL, condition_id UUID NOT NULL, kind VARCHAR(16) NOT NULL, product_id UUID DEFAULT NULL, type_id UUID DEFAULT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_DED870C7887793B6 ON discount_condition_target (condition_id)');
        $this->addSql('CREATE INDEX IDX_DED870C74584665A ON discount_condition_target (product_id)');
        $this->addSql('CREATE INDEX IDX_DED870C7C54C8C93 ON discount_condition_target (type_id)');
        $this->addSql('ALTER TABLE discount_condition_target ADD CONSTRAINT FK_DED870C7887793B6 FOREIGN KEY (condition_id) REFERENCES discount_condition (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE discount_condition_target ADD CONSTRAINT FK_DED870C74584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE discount_condition_target ADD CONSTRAINT FK_DED870C7C54C8C93 FOREIGN KEY (type_id) REFERENCES product_type (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('INSERT INTO discount_condition_target (id, condition_id, kind, product_id, type_id, variant) SELECT id, id, kind, product_id, type_id, variant FROM discount_condition');
        $this->addSql('ALTER TABLE discount_condition DROP CONSTRAINT fk_dd0c00bec54c8c93');
        $this->addSql('ALTER TABLE discount_condition DROP CONSTRAINT fk_dd0c00be4584665a');
        $this->addSql('DROP INDEX idx_dd0c00be4584665a');
        $this->addSql('DROP INDEX idx_dd0c00bec54c8c93');
        $this->addSql('ALTER TABLE discount_condition DROP kind');
        $this->addSql('ALTER TABLE discount_condition DROP product_id');
        $this->addSql('ALTER TABLE discount_condition DROP type_id');
        $this->addSql('ALTER TABLE discount_condition DROP variant');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE discount_condition ADD kind VARCHAR(16) DEFAULT NULL');
        $this->addSql('ALTER TABLE discount_condition ADD product_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE discount_condition ADD type_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE discount_condition ADD variant VARCHAR(100) DEFAULT NULL');
        $this->addSql('UPDATE discount_condition c SET kind = t.kind, product_id = t.product_id, type_id = t.type_id, variant = t.variant FROM (SELECT DISTINCT ON (condition_id) * FROM discount_condition_target ORDER BY condition_id, id) t WHERE t.condition_id = c.id');
        $this->addSql('DELETE FROM discount_condition WHERE kind IS NULL');
        $this->addSql('ALTER TABLE discount_condition ALTER kind SET NOT NULL');
        $this->addSql('ALTER TABLE discount_condition ADD CONSTRAINT fk_dd0c00bec54c8c93 FOREIGN KEY (type_id) REFERENCES product_type (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE discount_condition ADD CONSTRAINT fk_dd0c00be4584665a FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_dd0c00be4584665a ON discount_condition (product_id)');
        $this->addSql('CREATE INDEX idx_dd0c00bec54c8c93 ON discount_condition (type_id)');
        $this->addSql('DROP TABLE discount_condition_target');
    }
}
