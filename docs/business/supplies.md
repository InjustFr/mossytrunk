# Supplies (Fournitures)

A **supply** is something the workshop uses up but never sells: sleeves protecting stickers or prints, envelopes for
web orders, boxes, flyers left at the stall… It is a product of kind **Fourniture** (`Domain\Product\ProductKind::Supply`),
so it has a type, variants, a reference, stock lots (FIFO), supplier orders, restocks, a low-stock alert and inventory
counts exactly like an article (see [products.md](products.md), [stock.md](stock.md)).

## Rules

| # | Rule | Where | Tests |
|---|---|---|---|
| S1 | The kind is chosen when the product is created and does not change | `Product::supply()`, `CreateProductHandler` | `SupplyTest`, `ProductApiTest` |
| S2 | A supply has no price: its selling price is 0, it has no channel price and cannot be repriced; it is never sold (no order line, no discount rule, no catalogue export, never matched by an imported sale) | `Product::reprice()`, `setPriceOn()`, `sellableOn()` → `SupplyIsNotSold`; `ProductRepository::articles()` | `SupplyTest` |
| S3 | A channel offers some supplies; an order uses them by hand, one by one from the order page or in batch for orders of one channel (C10, O17). They leave the stock at that moment and their cost lowers the order's margin | `SalesChannel::offerSupplies()`, `Order::useSupply()` | `OrderSuppliesTest` |

## UI

- **Produits**: tabs « Articles » / « Fournitures » (`?kind=supply`); the supplies tab has no price, margin or sales columns and its button creates a « Nouvelle fourniture ». The product form chooses the kind (Article / Fourniture) at creation and hides prices for a supply.
- **Product page**: a supply shows its kind instead of prices, margin and sales.
