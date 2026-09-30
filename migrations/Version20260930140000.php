<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260930140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Product types own their variants; every product and gabarit has a type (« Divers » for the untyped ones); discount conditions may target a variant';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product_type ADD variants JSON DEFAULT \'[]\' NOT NULL');
        $this->addSql('ALTER TABLE product_type ADD prefixes_names BOOLEAN DEFAULT true NOT NULL');
        $this->addSql('ALTER TABLE discount_condition ADD variant VARCHAR(100) DEFAULT NULL');

        $this->addSql(<<<'SQL'
            INSERT INTO product_type (id, workspace_id, name, code, color, variants, prefixes_names)
            SELECT gen_random_uuid(), w.id, 'Divers',
                CASE WHEN EXISTS (SELECT 1 FROM product_type t WHERE t.workspace_id = w.id AND t.code = 'DIV')
                    THEN 'DIV' || (SELECT COUNT(*) + 2 FROM product_type t WHERE t.workspace_id = w.id)
                    ELSE 'DIV' END,
                '#9c7b5b', '[]', false
            FROM workspace w
            WHERE (EXISTS (SELECT 1 FROM product p WHERE p.workspace_id = w.id AND p.type_id IS NULL)
                OR EXISTS (SELECT 1 FROM gabarit g WHERE g.workspace_id = w.id AND g.type_id IS NULL))
              AND NOT EXISTS (SELECT 1 FROM product_type t WHERE t.workspace_id = w.id AND LOWER(t.name) = 'divers')
            SQL);
        $this->addSql("UPDATE product SET type_id = (SELECT t.id FROM product_type t WHERE t.workspace_id = product.workspace_id AND LOWER(t.name) = 'divers' LIMIT 1) WHERE type_id IS NULL");
        $this->addSql("UPDATE gabarit SET type_id = (SELECT t.id FROM product_type t WHERE t.workspace_id = gabarit.workspace_id AND LOWER(t.name) = 'divers' LIMIT 1) WHERE type_id IS NULL");

        $this->addSql(<<<'SQL'
            UPDATE product_type t SET variants = COALESCE((
                SELECT json_agg(first.label ORDER BY first.position)
                FROM (
                    SELECT DISTINCT ON (LOWER(numbered.label)) numbered.label, numbered.position
                    FROM (
                        SELECT used.label, ROW_NUMBER() OVER (ORDER BY used.source, used.at NULLS LAST, used.owner, used.ordinal) AS position
                        FROM (
                            SELECT e.label, 0 AS source, p.created_at AS at, p.id::text AS owner, e.ordinal
                            FROM product p, json_array_elements_text(p.variants) WITH ORDINALITY AS e(label, ordinal)
                            WHERE p.type_id = t.id
                            UNION ALL
                            SELECT e.label, 1, NULL, g.id::text, e.ordinal
                            FROM gabarit g, json_array_elements_text(g.variants) WITH ORDINALITY AS e(label, ordinal)
                            WHERE g.type_id = t.id
                            UNION ALL
                            SELECT e.label, 2, d.created_at, d.id::text, e.ordinal
                            FROM design_declination d JOIN gabarit g ON g.id = d.gabarit_id, json_array_elements_text(d.variants) WITH ORDINALITY AS e(label, ordinal)
                            WHERE g.type_id = t.id
                        ) AS used
                    ) AS numbered
                    ORDER BY LOWER(numbered.label), numbered.position
                ) AS first
            ), '[]'::json)
            SQL);

        $this->addSql('ALTER TABLE gabarit DROP CONSTRAINT fk_c49f1c54c54c8c93');
        $this->addSql('ALTER TABLE gabarit ALTER type_id SET NOT NULL');
        $this->addSql('ALTER TABLE gabarit ADD CONSTRAINT FK_C49F1C54C54C8C93 FOREIGN KEY (type_id) REFERENCES product_type (id) NOT DEFERRABLE');
        $this->addSql('ALTER TABLE product DROP CONSTRAINT fk_d34a04adc54c8c93');
        $this->addSql('ALTER TABLE product ALTER type_id SET NOT NULL');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04ADC54C8C93 FOREIGN KEY (type_id) REFERENCES product_type (id) NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product DROP CONSTRAINT FK_D34A04ADC54C8C93');
        $this->addSql('ALTER TABLE product ALTER type_id DROP NOT NULL');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT fk_d34a04adc54c8c93 FOREIGN KEY (type_id) REFERENCES product_type (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE gabarit DROP CONSTRAINT FK_C49F1C54C54C8C93');
        $this->addSql('ALTER TABLE gabarit ALTER type_id DROP NOT NULL');
        $this->addSql('ALTER TABLE gabarit ADD CONSTRAINT fk_c49f1c54c54c8c93 FOREIGN KEY (type_id) REFERENCES product_type (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE discount_condition DROP variant');
        $this->addSql('ALTER TABLE product_type DROP prefixes_names');
        $this->addSql('ALTER TABLE product_type DROP variants');
    }
}
