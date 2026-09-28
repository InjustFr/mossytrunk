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

A managed list (`ProductType`: unique `name`, unique short `code` such as `PRI`, derived from the name at creation and made unique `PRI2`…).
Types are created from `/api/product-types` or **inline from the product form** (« ＋ Créer un type… »). Renaming a type renames how its products are displayed; past orders keep their snapshot.

## Rules

| # | Rule | Where | Tests |
|---|---|---|---|
| P1 | Reference and name are required (trimmed) | `Product::__construct()`, `Product::rename()` | `ProductTest` |
| P2 | Reference is generated as `{TYPE CODE or PRD}-{NAME SLUG}` (accents removed, max 40 chars), suffixed `-2`, `-3`… when taken; it never changes afterwards (renaming or re-typing keeps it) | `ProductReferenceGenerator`, `CreateProductHandler`, SumUp import (+ DB unique index) | `ProductReferenceGeneratorTest`, `ProductUseCasesTest` |
| P3 | Prices are never negative; buying price defaults to 0 | `Product::reprice()`, `Product::create()` | `ProductTest` |
| P4 | Variants are non-empty and unique per product; replacing the list is all-or-nothing | `Product::addVariant()`, `replaceVariants()` | `ProductTest` |
| P6 | A product is displayed (lists, pickers, order lines, reports) as `displayName()` = « {type} {name} », or its name when untyped. Order lines snapshot that display name | `Product::displayName()`, `Product::sellable()` | `ProductTypeTest` |
| P7 | Type names are unique (case-insensitive); type codes are unique, 1–8 uppercase letters/digits | `CreateProductTypeHandler`, `RenameProductTypeHandler`, `ProductType` | `ProductTypeTest`, `ProductTypeUseCasesTest` |
| P5 | **What is sold is a (product ULID, variant) tuple.** A product with variants requires one of *its* variants; a unique product accepts no variant | `Product::sellable(?variant)` → `SellableItem` | `ProductTest` |

`SellableItem` is the only way to obtain a sellable tuple, so an order line can never reference an invalid product/variant pair. It also carries the selling and buying prices at the time of sale: orders **snapshot** them, so later price changes never alter past orders.

## Use cases & API

| Use case | Endpoint |
|---|---|
| `CreateProduct` | `POST /api/products` `{typeId?, name, sellingPrice, buyingPrice?, variants[]}` |
| `UpdateProduct` | `PUT /api/products/{id}` (same body) |
| `ListProducts` | `GET /api/products` (sorted by type then name; includes `displayName`, `typeId`, `typeName`) |
| `CreateProductType` / `RenameProductType` / `ListProductTypes` | `POST` / `PUT /{id}` / `GET /api/product-types` `{name}` |

UI: `/produits` (`ProductsPage.vue`) — list on the left, create/edit form on the right. Products with a buying price of 0 show a ⚠︎ to remind that the margin is overstated.
