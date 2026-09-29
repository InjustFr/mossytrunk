# Products (Produits)

A **product** is a real item sold at events: sticker, print, T-shirt, original artwork…

| Field | Meaning |
|---|---|
| `reference` | Unique business code **generated at creation** from type code + name (`PRI-FORET`, `PRD-…` when untyped, `-2` suffix if taken), then **fixed** |
| `type` | Optional **product type** (Print, Sticker, T-shirt…) |
| `name` | Specific name; the product is **displayed as « {type} {name} »** (type Print + name « Forêt » = « Print Forêt ») |
| `sellingPrice` | Default price charged to customers (cents) |
| `buyingPrice` | What one unit costs the business (cents). **0 = unknown** (e.g. after a SumUp import), editable later |
| `variants` | Free-text labels (colour, size, design…). Empty list = **unique product** |

Model: `src/Domain/Product/Product.php` (Doctrine entity), `ProductType.php`, `SellableItem.php`.

## Product types

A managed list (`ProductType`: `name` and short `code` unique within the workspace such as `PRI`, derived from the name at creation and made unique `PRI2`…).
Types are created from `/api/product-types` or **inline from the product form** (« ＋ Créer un type… »). Renaming a type renames how its products are displayed; past orders keep their snapshot.

## Rules

| # | Rule | Where | Tests |
|---|---|---|---|
| P1 | Reference and name are required (trimmed) | `Product::__construct()`, `Product::rename()` | `ProductTest` |
| P2 | Reference is generated as `{TYPE CODE or PRD}-{NAME SLUG}` (accents removed, max 40 chars), suffixed `-2`, `-3`… when taken; it never changes afterwards (renaming or re-typing keeps it) | `ProductReferenceGenerator`, `CreateProductHandler`, SumUp import (+ DB unique index on workspace + reference) | `ProductReferenceGeneratorTest`, `ProductUseCasesTest` |
| P3 | Prices are never negative; buying price defaults to 0 | `Product::reprice()`, `Product::create()` | `ProductTest` |
| P4 | Variants are non-empty and unique per product; replacing the list is all-or-nothing | `Product::addVariant()`, `replaceVariants()` | `ProductTest` |
| P6 | A product is displayed (lists, pickers, order lines, reports) as `displayName()` = « {type} {name} », or its name when untyped. Order lines snapshot that display name | `Product::displayName()`, `Product::sellable()` | `ProductTypeTest` |
| P7 | Within a workspace, type names are unique (case-insensitive) and type codes are unique, 1–8 uppercase letters/digits | `CreateProductTypeHandler`, `RenameProductTypeHandler`, `ProductType` | `ProductTypeTest`, `ProductTypeUseCasesTest` |
| P5 | **What is sold is a (product ULID, variant) tuple.** A product with variants requires one of *its* variants; a unique product accepts no variant | `Product::sellable(?variant)` → `SellableItem` | `ProductTest` |

`SellableItem` is the only way to obtain a sellable tuple, so an order line can never reference an invalid product/variant pair. It also carries the selling and buying prices at the time of sale: orders **snapshot** them, so later price changes never alter past orders.

## Filters & batch edit

The product list can be filtered by type (chips, incl. « Sans type ») and text. Ticked products (« Tout sélectionner » ticks what is visible)
can be edited together: selling price, buying price, type, variants to add (skipped when already present), variants to remove.

| # | Rule | Where | Tests |
|---|---|---|---|
| P8 | A batch edit goes through the same entity methods as a single edit; any violation (e.g. negative price) aborts the whole batch | `BatchUpdateProductsHandler` | `BatchUpdateProductsTest` |

## Moving a variant

A product split per variant (« Mug Lichen », « Mug Fougère ») can be gathered into one product with variants, and a variant can move to another product:

| # | Rule | Where | Tests |
|---|---|---|---|
| P10 | The moved part is one variant of the source, or the **whole source** when it has no variants. The target is an existing product (not the source) or a new product with the source's type and prices | `MoveVariantHandler` | `MoveVariantTest` |
| P11 | The target variant is created when missing; left empty, sales merge into a target without variants. A target without variants that already has sales cannot get its first variant (those sales would have none) | `MoveVariantHandler`, `InvalidProduct::soldWithoutVariant()` | `MoveVariantTest` |
| P12 | Every order line of the moved (product, variant) now sells the target: name and variant change, **prices stay** as sold. A line merges into a line of the same order already selling the target at the same prices | `Order::moveSales()`, `OrderLine::reassign()` | `OrderTest`, `MoveVariantTest` |
| P13 | A source left without anything to sell (whole product moved, or its last variant) is **deleted**; bundle discounts listing it list the target instead | `MoveVariantHandler`, `DiscountRule::replaceEligibleProduct()` | `DiscountRuleTest`, `MoveVariantTest` |

UI: row action « Faire de … une variante » / « Déplacer une variante de … » on `/produits`. For a whole product, the form suggests the name without its last word as the target and that word as the variant (« Mug Lichen » → « Mug » + Lichen).

## Deleting a product

| # | Rule | Where | Tests |
|---|---|---|---|
| P14 | A deleted product leaves the catalogue and the bundle discounts listing it. Past orders keep their lines (name and prices are snapshots); reports show them under « Sans type » | `DeleteProductHandler`, `DiscountRule::withdrawProduct()` | `DeleteProductTest`, `DiscountRuleTest` |
| P15 | A product that a discount targets **alone** (no other product nor type) cannot be deleted: change or delete that discount first | `InvalidDiscountRule::onlyEligibleProduct()` | `DeleteProductTest`, `DiscountRuleTest` |

UI: trash icon on each row of `/produits`, with a confirmation.

## Use cases & API

| Use case | Endpoint |
|---|---|
| `CreateProduct` | `POST /api/products` `{typeId?, name, sellingPrice, buyingPrice?, variants[]}` |
| `UpdateProduct` | `PUT /api/products/{id}` (same body) |
| `BatchUpdateProducts` | `POST /api/products/batch` `{productIds[], sellingPrice?, buyingPrice?, changeType, typeId?, addVariants[], removeVariants[]}` → `{updated}` |
| `MoveVariant` | `POST /api/products/{id}/move-variant` `{variant?, targetProductId? \| newProductName?, targetVariant?}` → `{targetProductId}` |
| `DeleteProduct` | `DELETE /api/products/{id}` → 204 |
| `ListProducts` | `GET /api/products` (sorted by type then name; includes `displayName`, `typeId`, `typeName`) |
| `CreateProductType` / `RenameProductType` / `ListProductTypes` | `POST` / `PUT /{id}` / `GET /api/product-types` `{name}` |

`ListProducts` also returns each product's sales of the **current year** (Europe/Paris): `salesYear`, `unitsSold` and `sales` (line totals before discounts, every variant together, see [dashboard](dashboard.md) B7).

UI: `/produits` (`ProductsPage.vue`) — filters, list with selection, create/edit and batch edit in modals. The list is sortable and shows each product's type (with a colour mark, one colour per type in alphabetical order), margin (selling − buying price, and its share of the selling price), units sold and sales of the year.
Products with a buying price of 0 show a warning icon (Lucide `TriangleAlert`) to remind that the margin is overstated, and no margin. A « N prix d'achat à renseigner » toggle keeps only those products (`/produits?prix-achat=manquant`, linked from the dashboard warning).
