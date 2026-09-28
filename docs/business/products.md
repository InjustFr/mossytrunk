# Products (Produits)

A **product** is a real item sold at events: sticker, print, T-shirt, original artwork…

| Field | Meaning |
|---|---|
| `reference` | Unique business code (e.g. `TS-01`, `SU-…` for SumUp imports) |
| `name` | Display name |
| `sellingPrice` | Default price charged to customers (cents) |
| `buyingPrice` | What one unit costs the business (cents). **0 = unknown** (e.g. after a SumUp import), editable later |
| `variants` | Free-text labels (colour, size, design…). Empty list = **unique product** |

Model: `src/Domain/Product/Product.php` (Doctrine entity), `SellableItem.php`.

## Rules

| # | Rule | Where | Tests |
|---|---|---|---|
| P1 | Reference and name are required (trimmed) | `Product::describe()` | `ProductTest` |
| P2 | Reference is unique across products | `CreateProductHandler`, `UpdateProductHandler` (+ DB unique index) | `ProductUseCasesTest` |
| P3 | Prices are never negative; buying price defaults to 0 | `Product::reprice()`, `Product::create()` | `ProductTest` |
| P4 | Variants are non-empty and unique per product; replacing the list is all-or-nothing | `Product::addVariant()`, `replaceVariants()` | `ProductTest` |
| P5 | **What is sold is a (product ULID, variant) tuple.** A product with variants requires one of *its* variants; a unique product accepts no variant | `Product::sellable(?variant)` → `SellableItem` | `ProductTest` |

`SellableItem` is the only way to obtain a sellable tuple, so an order line can never reference an invalid product/variant pair. It also carries the selling and buying prices at the time of sale: orders **snapshot** them, so later price changes never alter past orders.

## Use cases & API

| Use case | Endpoint |
|---|---|
| `CreateProduct` | `POST /api/products` `{reference, name, sellingPrice, buyingPrice?, variants[]}` |
| `UpdateProduct` | `PUT /api/products/{id}` (same body) |
| `ListProducts` | `GET /api/products` (sorted by name) |

UI: `/produits` (`ProductsPage.vue`) — list on the left, create/edit form on the right. Products with a buying price of 0 show a ⚠︎ to remind that the margin is overstated.
