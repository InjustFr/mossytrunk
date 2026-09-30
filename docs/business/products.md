# Products (Produits)

A **product** is a real item sold at events: sticker, print, T-shirt, original artwork…

| Field | Meaning |
|---|---|
| `reference` | Unique business code **generated at creation** from type code + name (`PRI-FORET`, `PRD-…` when untyped, `-2` suffix if taken), then **fixed** |
| `type` | Optional **product type** (Print, Sticker, T-shirt…) |
| `name` | Specific name; the product is **displayed as « {type} {name} »** (type Print + name « Forêt » = « Print Forêt ») |
| `sellingPrice` | Default price charged to customers (cents) |
| `buyingPrice` | **Last purchase price**, read-only: set only by restocking or receiving a supplier order (see [stock.md](stock.md)); a new product starts at **0 = never bought** (cost unknown) |
| `lowStockThreshold` | « Alerte stock bas », default 10 (see [stock.md](stock.md)) |
| `variants` | Free-text labels (colour, size, design…). Empty list = **unique product** |

Model: `src/Domain/Product/Product.php` (Doctrine entity), `ProductType.php`, `SellableItem.php`.

## Product types

A managed list (`ProductType`: `name` and short `code` unique within the workspace such as `PRI`, derived from the name at creation and made unique `PRI2`…, and a `color`).
Types are managed from `/produits` (header button « Types de produit »: list, « Ajouter un type », edit name and colour), created **inline from the product form** (« ＋ Créer un type… ») or by an import; the code never changes. Renaming a type renames how its products are displayed; past orders keep their snapshot.

The colour marks the type everywhere it is shown (product list and filters, dashboard best sellers, event order recap). It is picked from a palette of 12 colours, or chosen freely (« Couleur personnalisée »: colour area, hue slider, hex code); a custom colour used by a type is offered in the palette of every type of the workspace. A type created without a colour (import) takes the next palette colour.

## Rules

| # | Rule | Where | Tests |
|---|---|---|---|
| P1 | Reference and name are required (trimmed) | `Product::__construct()`, `Product::rename()` | `ProductTest` |
| P2 | Reference is generated as `{TYPE CODE or PRD}-{NAME SLUG}` (accents removed, max 40 chars), suffixed `-2`, `-3`… when taken; it never changes afterwards (renaming or re-typing keeps it) | `ProductReferenceGenerator`, `CreateProductHandler`, SumUp import (+ DB unique index on workspace + reference) | `ProductReferenceGeneratorTest`, `ProductUseCasesTest` |
| P3 | Prices are never negative. Only the selling price is entered by the user; the buying price starts at 0 and changes only through purchases (`Product::bought()`: restock, supplier order reception, or copied when a variant moves to a new product). Editing a product never changes it | `Product::reprice()`, `Product::bought()` | `ProductTest`, `ProductUseCasesTest` |
| P4 | Variants are non-empty and unique per product; replacing the list is all-or-nothing | `Product::addVariant()`, `replaceVariants()` | `ProductTest` |
| P6 | A product is displayed (lists, pickers, order lines, reports) as `displayName()` = « {type} {name} », or its name when untyped. Order lines snapshot that display name | `Product::displayName()`, `Product::sellable()` | `ProductTypeTest` |
| P7 | Within a workspace, type names are unique (case-insensitive) and type codes are unique, 1–8 uppercase letters/digits | `ProductTypeCreator`, `UpdateProductTypeHandler`, `ProductType` | `ProductTypeTest`, `ProductTypeUseCasesTest` |
| P19 | A type colour is a `#rrggbb` hex colour (stored lowercase). Without one, a new type takes palette colour n° (number of types) | `ProductType::recolor()`, `CreateProductTypeHandler` | `ProductTypeTest`, `ProductTypeUseCasesTest` |
| P5 | **What is sold is a (product ULID, variant) tuple.** A product with variants requires one of *its* variants; a unique product accepts no variant | `Product::sellable(?variant)` → `SellableItem` | `ProductTest` |

`SellableItem` is the only way to obtain a sellable tuple, so an order line can never reference an invalid product/variant pair. It also carries the selling and buying prices at the time of sale: orders **snapshot** them, so later price changes never alter past orders.

## Filters & batch edit

The product list can be filtered by type (chips, incl. « Sans type ») and text. Ticked products (« Tout sélectionner » ticks what is visible)
can be edited together: selling price, type, variants to add (skipped when already present), variants to remove.

| # | Rule | Where | Tests |
|---|---|---|---|
| P8 | A batch edit goes through the same entity methods as a single edit; any violation (e.g. negative price) aborts the whole batch | `BatchUpdateProductsHandler` | `BatchUpdateProductsTest` |

## Moving a variant

A product split per variant (« Mug Lichen », « Mug Fougère ») can be gathered into one product with variants, and a variant can move to another product:

| # | Rule | Where | Tests |
|---|---|---|---|
| P10 | The moved part is one variant of the source, or the **whole source** when it has no variants. The target is an existing product (not the source) or a new product with the source's type and prices | `MoveVariantHandler` | `MoveVariantTest` |
| P11 | The target variant is created when missing; left empty, sales merge into a target without variants. A target without variants that already has sales cannot get its first variant (those sales would have none) | `MoveVariantHandler`, `SoldWithoutVariant` | `MoveVariantTest` |
| P12 | Every order line of the moved (product, variant) now sells the target: name and variant change, **prices stay** as sold. A line merges into a line of the same order already selling the target at the same prices | `Order::moveSales()`, `OrderLine::reassign()` | `OrderTest`, `MoveVariantTest` |
| P13 | A source left without anything to sell (whole product moved, or its last variant) is **deleted**; discount conditions on it target the product it went into instead (quantities added when that product already has a condition) | `MoveVariantHandler`, `DiscountRule::replaceProduct()` | `DiscountRuleTest`, `MoveVariantTest` |

UI: row action « Faire de … une variante » / « Déplacer une variante de … » on `/produits`. For a whole product, the form suggests the name without its last word as the target and that word as the variant (« Mug Lichen » → « Mug » + Lichen).

## Deleting a product

| # | Rule | Where | Tests |
|---|---|---|---|
| P14 | A deleted product leaves the catalogue, and the discount conditions on it are removed. Past orders keep their lines (name and prices are snapshots); reports show them under « Sans type » | `DeleteProductHandler`, `DiscountRule::withdrawProduct()` | `DeleteProductTest`, `DiscountRuleTest` |
| P15 | A product that is the **only condition** of a discount cannot be deleted: change or delete that discount first | `OnlyEligibleProduct` | `DeleteProductTest`, `DiscountRuleTest` |

| P16 | Every product of the workspace can be deleted at once, like P14. Discounts with product conditions only are deleted with them; those also having type conditions keep those | `DeleteAllProductsHandler`, `DiscountRule::withdrawEveryProduct()` | `DeleteAllProductsTest`, `DiscountRuleTest` |
| P17 | Every selling price a product has had is kept with its date (creation, then each change; setting the same price records nothing). Products that existed before the history start with their current price at their creation date | `Product::reprice()`, `SellingPriceChange` | `ProductTest`, `GetProductTest` |
| P18 | The price history can be corrected: add a past price with its day, change an entry's price or day, delete an entry (never the last one). Days are Europe/Paris, never in the future. The **selling price is always the entry with the latest day**, so correcting the current entry fixes the price without adding a line. Past orders keep their own price snapshots | `Product::recordPrice()`, `amendPrice()`, `forgetPrice()` | `ProductTest`, `SellingPriceApiTest` |

UI: trash icon on each row of `/produits`, with a confirmation. `/parametres` — « Zone de danger »: delete every product, behind a warning and a confirmation.

## Use cases & API

| Use case | Endpoint |
|---|---|
| `CreateProduct` | `POST /api/products` `{typeId?, name, sellingPrice, variants[], lowStockThreshold?}` |
| `UpdateProduct` | `PUT /api/products/{id}` (same body) |
| `BatchUpdateProducts` | `POST /api/products/batch` `{productIds[], sellingPrice?, changeType, typeId?, addVariants[], removeVariants[]}` → `{updated}` |
| `GetProduct` | `GET /api/products/{id}` → product figures, all-time sales, stock per variant with lots, movements (purchases, supplier receptions, returns, inventory surplus, sales, inventory losses; newest first), selling price history, design |
| `RecordSellingPrice`, `AmendSellingPrice`, `ForgetSellingPrice` | `POST /api/products/{id}/prices`, `PUT` / `DELETE /api/products/{id}/prices/{changeId}` `{price, since: YYYY-MM-DD}` |
| `DesignProduct` | `POST /api/products/{id}/design` `{gabaritId, designId?, collectionId?}` → `{designId}` (see [designs.md](designs.md) D8) |
| `MoveVariant` | `POST /api/products/{id}/move-variant` `{variant?, targetProductId? \| newProductName?, targetVariant?}` → `{targetProductId}` |
| `DeleteProduct` | `DELETE /api/products/{id}` → 204 |
| `DeleteAllProducts` | `DELETE /api/products` → `{deleted}` |
| `ListProducts` | `GET /api/products` (sorted by type then name; includes `displayName`, `typeId`, `typeName`) |
| `CreateProductType` / `UpdateProductType` / `ListProductTypes` | `POST` `{name, color?}` / `PUT /{id}` `{name, color}` / `GET /api/product-types` → `{id, name, code, color}` |

`ListProducts` also returns each product's sales of the **current year** (Europe/Paris): `salesYear`, `unitsSold` and `sales` (line totals before discounts, every variant together, see [dashboard](dashboard.md) B7).

UI: `/produits` (`ProductsPage.vue`) — filters, list with selection, create/edit and batch edit in modals. The list is sortable and shows each product's type (with its colour mark), stock, cost (stock cost, see K10), margin (selling − cost, and its share of the selling price), units sold and sales of the year.
Products never bought (buying price 0) show a warning icon (Lucide `TriangleAlert`) to remind that the margin is overstated, and no margin. A « N produits sans coût d'achat » toggle keeps only those products (`/produits?prix-achat=manquant`, linked from the dashboard warning).

**Product page** `/produits/{id}` (`ProductDetailPage.vue`, product names in the list link to it): key figures (reference, type, variants, selling price, stock cost, margin, stock with badge, units sold this year and ever), « Stock » (lots per variant, oldest sold first), « Mouvements » (paginated timeline, links to the order, supplier order or event), « Prix de vente » (history, current price first with the change from the previous one; each entry can be edited or deleted, « Ajouter un prix passé ») and « Design » (link to its design, or « Créer son design » / « Rattacher à un design »). Header: « Modifier », « Réapprovisionner ».
