<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261003070630 extends AbstractMigration
{
    private const array ORDER_FIGURES = ['subtotal_cents', 'discount_total_cents', 'total_cents', 'cost_of_goods_cents', 'supplies_cost_cents', 'channel_costs_cents'];

    public function getDescription(): string
    {
        return 'Orders record their figures and each line its share of the discounts';
    }

    public function up(Schema $schema): void
    {
        foreach (self::ORDER_FIGURES as $column) {
            $this->addSql(\sprintf('ALTER TABLE "order" ADD %s INT DEFAULT 0 NOT NULL', $column));
        }
        $this->addSql('ALTER TABLE order_line ADD discount_cents INT DEFAULT 0 NOT NULL');

        $this->addSql(<<<'SQL'
            UPDATE "order" o SET
                subtotal_cents = COALESCE((SELECT SUM(l.unit_price_cents * l.quantity) FROM order_line l WHERE l.order_id = o.id), 0),
                discount_total_cents = COALESCE((SELECT SUM((d->>'amount')::int) FROM json_array_elements(o.applied_discounts) d), 0),
                cost_of_goods_cents = COALESCE((SELECT SUM(l.cost_cents) FROM order_line l WHERE l.order_id = o.id), 0),
                supplies_cost_cents = COALESCE((SELECT SUM(s.cost_cents) FROM order_supply s WHERE s.order_id = o.id), 0),
                channel_costs_cents = o.postage_cents + COALESCE((SELECT SUM((c->>'amount')::int) FROM json_array_elements(o.channel_charges) c), 0)
            SQL);
        $this->addSql('UPDATE "order" SET total_cents = subtotal_cents - discount_total_cents + shipping_cents');

        $this->addSql(<<<'SQL'
            WITH lines AS (
                SELECT l.id, l.order_id,
                    ROW_NUMBER() OVER (PARTITION BY l.order_id ORDER BY l.id) AS position,
                    COUNT(*) OVER (PARTITION BY l.order_id) AS parts,
                    l.unit_price_cents::bigint * l.quantity AS amount,
                    (SUM(l.unit_price_cents::bigint * l.quantity) OVER (PARTITION BY l.order_id))::bigint AS subtotal,
                    o.discount_total_cents::bigint AS discount
                FROM order_line l
                JOIN "order" o ON o.id = l.order_id
            ), shares AS (
                SELECT id, order_id, position, subtotal, discount,
                    CASE WHEN subtotal = 0 THEN discount / parts ELSE discount * amount / subtotal END AS base,
                    CASE WHEN subtotal = 0 THEN 0 ELSE discount * amount % subtotal END AS remainder
                FROM lines
            ), ranked AS (
                SELECT id, base,
                    discount - SUM(base) OVER (PARTITION BY order_id) AS leftover,
                    CASE WHEN subtotal = 0 THEN position ELSE ROW_NUMBER() OVER (PARTITION BY order_id ORDER BY remainder DESC, position) END AS turn
                FROM shares
            )
            UPDATE order_line l
            SET discount_cents = r.base + CASE WHEN r.turn <= r.leftover THEN 1 ELSE 0 END
            FROM ranked r
            WHERE r.id = l.id
            SQL);

        foreach (self::ORDER_FIGURES as $column) {
            $this->addSql(\sprintf('ALTER TABLE "order" ALTER %s DROP DEFAULT', $column));
        }
        $this->addSql('ALTER TABLE order_line ALTER discount_cents DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        foreach (self::ORDER_FIGURES as $column) {
            $this->addSql(\sprintf('ALTER TABLE "order" DROP %s', $column));
        }
        $this->addSql('ALTER TABLE order_line DROP discount_cents');
    }
}
