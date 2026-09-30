# Products (Produits)

A **product** is a real item sold at events: sticker, print, T-shirt, original artwork…

| Field | Meaning |
|---|---|
| `reference` | Unique business code, **suggested** from type code + first three letters of the name (`PRI-FOR`, `-2` suffix if taken); the user may type another one at creation and **change it later** |
| `type` | **Required** product type (Print, Sticker, T-shirt…) |
| `name` | Specific name; the product is **displayed as « {type} {name} »** (type Print + name « Forêt » = « Print Forêt »), or « {name} » when its type does not prefix names |
| `sellingPrice` | Default price charged to customers (cents) |
| `buyingPrice` | **Last purchase price**, read-only: set only by restocking or receiving a supplier order (see [stock.md](stock.md)); a new product starts at **0 = never bought** (cost unknown) |
| `lowStockThreshold` | « Alerte stock bas », default 10 (see [stock.md](stock.md)) |
| `variants` | Some of its **type's variants** (format, size, colour…). Empty list = **unique product** |

Model: `src/Domain/Product/Product.php` (Doctrine entity), `ProductType.php`, `SellableItem.php`.

## Product types

A managed list (`ProductType`: `name` and short `code` unique within the workspace such as `PRI`, a `color`, an ordered list of **variants** such as A5 / A4 / A3, and « préfixer le nom des produits » (`prefixesNames`, on by default)).
Every product has a type. Products and gabarits that had none were given a type « Divers » (not prefixing names, so « Aquarelle » stays « Aquarelle »); imports without category and free amounts use it too (created when missing, named after the user's language). The code prefixes the references suggested for its products; it is **suggested** from the first three letters of the name (made unique `PRI2`…), and the user may type another one at creation and change it later (existing product references do not change).
Types are managed from `/products` (header button « Types de produit »: list, « Ajouter un type », edit name, code, colour, variants and name prefix), created **inline from the product form** (« ＋ Créer un type… », suggested code) or by an import (suggested code). Renaming a type renames how its products are displayed; past orders keep their snapshot.

The colour marks the type everywhere it is shown (product list and filters, dashboard best sellers, event order recap). It is picked from a palette of 12 colours, or chosen freely (« Couleur personnalisée »: colour area, hue slider, hex code); a custom colour used by a type is offered in the palette of every type of the workspace. A type created without a colour (import) takes the next palette colour.

## Rules

| # | Rule | Where | Tests |
|---|---|---|---|
| P1 | Reference and name are required (trimmed) | `Product::__construct()`, `Product::rename()` | `ProductTest` |
| P2 | The suggested reference is `{TYPE CODE or PRD}-{first 3 letters/digits of the name}` (accents removed, `X` when none), suffixed `-2`, `-3`… when taken. The product form fills it in while the name/type are typed, until the user types their own; a blank reference at creation takes the suggestion (imports and designs always do). A chosen reference (creation or edit, max 64 chars) must be unique in the workspace; renaming or re-typing a product never changes it | `ProductReferenceGenerator`, `Product::changeReference()`, `ReferenceAvailability`, `CreateProductHandler`, `UpdateProductHandler` (+ DB unique index on workspace + reference) | `ProductReferenceGeneratorTest`, `ProductTest`, `ProductUseCasesTest`, `ProductApiTest` |
| P3 | Prices are never negative. Only the selling price is entered by the user; the buying price starts at 0 and changes only through purchases (`Product::bought()`: restock, supplier order reception, or copied when a variant moves to a new product). Editing a product never changes it | `Product::reprice()`, `Product::bought()` | `ProductTest`, `ProductUseCasesTest` |
| P4 | Variants are non-empty and unique per product; replacing the list is all-or-nothing | `Product::addVariant()`, `replaceVariants()` | `ProductTest` |
| P6 | A product is displayed (lists, pickers, order lines, reports) as `displayName()` = « {type} {name} », or its name when its type does not prefix names. Order lines snapshot that display name | `Product::displayName()`, `Product::sellable()` | `ProductTypeTest` |
| P7 | Within a workspace, type names are unique (case-insensitive) and type codes are unique, 1–8 letters/digits (stored uppercase). The suggested code is the first three letters/digits of the name (`TYP` when none), suffixed `2`, `3`… when taken; a blank code at creation takes the suggestion | `ProductTypeCreator`, `TypeCodeGenerator`, `UpdateProductTypeHandler`, `ProductType::recode()` | `ProductTypeTest`, `ProductTypeUseCasesTest`, `ProductTypeApiTest` |
| P19 | A type colour is a `#rrggbb` hex colour (stored lowercase). Without one, a new type takes palette colour n° (number of types) | `ProductType::recolor()`, `CreateProductTypeHandler` | `ProductTypeTest`, `ProductTypeUseCasesTest` |
| P20 | A type's variants are non-empty and unique (case-insensitive). A product, gabarit or declination only has variants of its type. In the app, variants are **picked from the type's list** (chips in the product, gabarit, declination and batch forms; a select when moving a variant); new variants are created in « Types de produit » only. Imports still add an unknown variant to the type (nobody can pick during an import), with the type's spelling when it already exists in another case. Re-typing a product adds its variants to the new type. Variants are compared case-insensitively everywhere | `ProductType::offerVariant()`, `Product::addVariant()`, `Product::classify()`, `Gabarit::describe()`, `Declination::adjust()`, `VariantLabel` | `ProductTypeTest`, `ProductTest` |
| P21 | Editing a type's variants (add, remove, reorder) cannot remove a variant still used by a product of the type, a gabarit, a declination not yet produced or a discount condition | `UpdateProductTypeHandler`, `VariantUsage`, `VariantInUse` | `ProductTypeUseCasesTest` |
| P22 | **Renaming a variant** renames it everywhere: the type, its products, gabarits, declinations, discount conditions, stock, orders (past ones included), supplier orders, stock checks and linked service items | `RenameTypeVariantHandler`, `VariantRelabelling` | `TypeVariantsTest` |
| P23 | When its type has variants, a product created or edited in the app (form or batch edit) has **at least one of them**: the product form picks the type's first variant and never lets the last one be unticked; changing the type drops the variants the new type does not offer. Imports and variant moves are not held to it | `Product::assertVariantChosen()`, `CreateProductHandler`, `UpdateProductHandler`, `BatchUpdateProductsHandler` | `ProductTest`, `BatchUpdateProductsTest` |
| P5 | **What is sold is a (product ULID, variant) tuple.** A product with variants requires one of *its* variants; a unique product accepts no variant | `Product::sellable(?variant)` → `SellableItem` | `ProductTest` |

`SellableItem` is the only way to obtain a sellable tuple, so an order line can never reference an invalid product/variant pair. It also carries the selling and buying prices at the time of sale: orders **snapshot** them, so later price changes never alter past orders.

## Filters & batch edit

The product list can be filtered by type (chips) and text. When a type with variants is chosen, its variants appear as a second row of chips: picking some keeps the products having one of them, and the stock column then shows **only the stock of those variants**. Ticked products (« Tout sélectionner » ticks what is visible)
can be edited together: selling price, type, variants to add (skipped when already present), variants to remove. The batch form opens without focusing a field, so a tablet does not pop its keyboard up when only the type or variants change.

| # | Rule | Where | Tests |
|---|---|---|---|
| P8 | A batch edit goes through the same entity methods as a single edit; any violation (e.g. negative price) aborts the whole batch | `BatchUpdateProductsHandler` | `BatchUpdateProductsTest` |

## Archiving and deleting types

Products, types and a type's variants are **archived** when they are no longer made, to keep their history (orders, stock, reports) while decluttering the app.

| # | Rule | Where | Tests |
|---|---|---|---|
| P24 | An archived product, or any product of an archived type, is hidden from the catalogue (« N archivés » toggle on `/products` shows them) and from what is picked to enter something (order lines, supplier orders, discount conditions, variant moves). It keeps its stock, sales and reports; imports still match it. Restoring brings it back; a product archived by its type comes back with the type | `Product::archive()`, `restore()`, `isArchived()`, `ProductType::archive()` | `ProductTest`, `ProductTypeTest`, `ArchiveApiTest` |
| P25 | A type's variants can be archived (« Types de produit » › edit, archive icon per variant): they stay on the products having them but are no longer offered by the pickers (product, gabarit, declination, batch, order and supplier order forms, discount conditions). Renaming a variant keeps it archived; removing it forgets it | `ProductType::archiveVariants()`, `activeVariants()`, `Product::activeVariants()` | `ProductTypeTest`, `ProductTest`, `ArchiveApiTest` |
| P26 | A type can be **deleted** only when no product (archived ones included) and no gabarit uses it; its discount conditions are removed, unless one is the only condition of a discount (change or delete that discount first). Otherwise archive it | `DeleteProductTypeHandler`, `TypeStillUsed`, `DiscountRule::withdrawType()` | `DeleteProductTypeTest`, `DiscountRuleTest`, `ArchiveApiTest` |

UI: `/products` row action archive / unarchive (Lucide `Archive` / `ArchiveRestore`); « Types de produit »: archive or delete each type, archived types listed apart with « Désarchiver ».

## Moving a variant

A product split per variant (« Mug Lichen », « Mug Fougère ») can be gathered into one product with variants, and a variant can move to another product:

| # | Rule | Where | Tests |
|---|---|---|---|
| P10 | The moved part is one variant of the source, or the **whole source** when it has no variants. The target is an existing product (not the source) or a new product with the source's type and prices | `MoveVariantHandler` | `MoveVariantTest` |
| P11 | The target variant is created when missing; left empty, sales merge into a target without variants. A target without variants that already has sales cannot get its first variant (those sales would have none) | `MoveVariantHandler`, `SoldWithoutVariant` | `MoveVariantTest` |
| P12 | Every order line of the moved (product, variant) now sells the target: name and variant change, **prices stay** as sold. A line merges into a line of the same order already selling the target at the same prices | `Order::moveSales()`, `OrderLine::reassign()` | `OrderTest`, `MoveVariantTest` |
| P13 | A source left without anything to sell (whole product moved, or its last variant) is **deleted**; discount conditions on it target the product it went into instead (quantities added when that product already has a condition) | `MoveVariantHandler`, `DiscountRule::replaceProduct()` | `DiscountRuleTest`, `MoveVariantTest` |

UI: row action « Faire de … une variante » / « Déplacer une variante de … » on `/products`. For a whole product, the form suggests the name without its last word as the target and that word as the variant (« Mug Lichen » → « Mug » + Lichen).

## Deleting a product

| # | Rule | Where | Tests |
|---|---|---|---|
| P14 | A deleted product leaves the catalogue, and the discount conditions on it are removed. Past orders keep their lines (name and prices are snapshots); reports show them under « Sans type » | `DeleteProductHandler`, `DiscountRule::withdrawProduct()` | `DeleteProductTest`, `DiscountRuleTest` |
| P15 | A product that is the **only condition** of a discount cannot be deleted: change or delete that discount first | `OnlyEligibleProduct` | `DeleteProductTest`, `DiscountRuleTest` |

| P16 | Every product of the workspace can be deleted at once, like P14. Discounts with product conditions only are deleted with them; those also having type conditions keep those | `DeleteAllProductsHandler`, `DiscountRule::withdrawEveryProduct()` | `DeleteAllProductsTest`, `DiscountRuleTest` |
| P17 | Every selling price a product has had is kept with its date (creation, then each change; setting the same price records nothing). Products that existed before the history start with their current price at their creation date | `Product::reprice()`, `SellingPriceChange` | `ProductTest`, `GetProductTest` |
| P18 | The price history can be corrected: add a past price with its day, change an entry's price or day, delete an entry (never the last one). Days are Europe/Paris, never in the future. The **selling price is always the entry with the latest day**, so correcting the current entry fixes the price without adding a line. Past orders keep their own price snapshots | `Product::recordPrice()`, `amendPrice()`, `forgetPrice()` | `ProductTest`, `SellingPriceApiTest` |

UI: trash icon on each row of `/products`, with a confirmation. `/settings` — « Zone de danger »: delete every product, behind a warning and a confirmation.

## Use cases & API

| Use case | Endpoint |
|---|---|
| `CreateProduct` | `POST /api/products` `{typeId, name, reference?, sellingPrice, variants[], lowStockThreshold?}` (blank reference = suggestion) |
| `UpdateProduct` | `PUT /api/products/{id}` (same body; no reference = unchanged) |
| `SuggestProductReference` | `GET /api/products/reference-suggestion?name=&typeId=` → `{reference}` |
| `BatchUpdateProducts` | `POST /api/products/batch` `{productIds[], sellingPrice?, changeType, typeId?, addVariants[], removeVariants[]}` → `{updated}` |
| `GetProduct` | `GET /api/products/{id}` → product figures, all-time sales, stock per variant with lots, movements (purchases, supplier receptions, returns, inventory surplus, sales, inventory losses; newest first), selling price history, design |
| `RecordSellingPrice`, `AmendSellingPrice`, `ForgetSellingPrice` | `POST /api/products/{id}/prices`, `PUT` / `DELETE /api/products/{id}/prices/{changeId}` `{price, since: YYYY-MM-DD}` |
| `DesignProduct` | `POST /api/products/{id}/design` `{gabaritId, designId?, collectionId?}` → `{designId}` (see [designs.md](designs.md) D8) |
| `MoveVariant` | `POST /api/products/{id}/move-variant` `{variant?, targetProductId? \| newProductName?, targetVariant?}` → `{targetProductId}` |
| `DeleteProduct` | `DELETE /api/products/{id}` → 204 |
| `ArchiveProduct` / `RestoreProduct` | `PUT` / `DELETE /api/products/{id}/archive` → 204 |
| `ArchiveProductType` / `RestoreProductType` / `DeleteProductType` | `PUT` / `DELETE /api/product-types/{id}/archive`, `DELETE /api/product-types/{id}` → 204 |
| `DeleteAllProducts` | `DELETE /api/products` → `{deleted}` |
| `ListProducts` | `GET /api/products` (sorted by type then name; includes `displayName`, `typeId`, `typeName`, `activeVariants`, `archived`, `archivedItself`) |
| `CreateProductType` / `UpdateProductType` / `ListProductTypes` | `POST` `{name, color?, code?, variants?, prefixesNames?}` / `PUT /{id}` `{name, color, code?, variants?, prefixesNames?, archivedVariants?}` / `GET /api/product-types` → `{id, name, code, color, variants, prefixesNames, archivedVariants, archived}` |
| `RenameTypeVariant` | `POST /api/product-types/{id}/variant-renaming` `{from, to}` |
| `SuggestTypeCode` | `GET /api/product-types/code-suggestion?name=` → `{code}` |

`ListProducts` also returns each product's sales of the **current year** (Europe/Paris): `salesYear`, `unitsSold` and `sales` (line totals before discounts, every variant together, see [dashboard](dashboard.md) B7).

UI: `/products` (`ProductsPage.vue`) — filters, list with selection, create/edit and batch edit in modals. The list is sortable and shows each product's type (with its colour mark), stock, cost (stock cost, see K10), margin (selling − cost, and its share of the selling price), units sold and sales of the year.
Products never bought (buying price 0) show a warning icon (Lucide `TriangleAlert`) to remind that the margin is overstated, and no margin. A « N produits sans coût d'achat » toggle keeps only those products (`/products?purchase-price=missing`, linked from the dashboard warning).

**Product page** `/products/{id}` (`ProductDetailPage.vue`, product names in the list link to it): key figures (reference, type, variants, selling price, stock cost, margin, stock with badge, units sold this year and ever), « Stock » (lots per variant, oldest sold first), « Mouvements » (paginated timeline, links to the order, supplier order or event), « Prix de vente » (history, current price first with the change from the previous one; each entry can be edited or deleted, « Ajouter un prix passé ») and « Design » (link to its design, or « Créer son design » / « Rattacher à un design »). Header: « Modifier », « Réapprovisionner ».
