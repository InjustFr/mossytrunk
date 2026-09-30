# Orders (Commandes)

An **order** is a sale made during an event, or an online sale imported from a connected service (then without event, see [imports.md](imports.md)).

| Field | Meaning |
|---|---|
| `reference` | `CMD-YYYYMMDD-XXXXXX` for manual orders; given by the service for imports (SumUp transaction code, `ETSY-<receipt id>`); unique within the workspace |
| `event` | The event the sale happened at (required, except for online imported orders) |
| `shipping` | Shipping charged to the customer by the service (Etsy), part of the total |
| `placedAt` | Date-time of the sale (stored with time zone, displayed in Europe/Paris) |
| `lines` | `(product ULID, variant)` tuple + quantity, with **snapshots** of product name, unit selling price and the **cost** of the units taken from stock |
| `appliedDiscounts` | Snapshot list of `{label, amount, ruleId}` (`ruleId` null for « Remise SumUp » and older orders) |
| `paymentMethod` | `card` or `cash`, given by the service on import (SumUp S13, Etsy card); none for manual orders |
| `source` | `manual` or the key of the service it was imported from (`sumup`, `etsy`) |
| `externalId` | The sale's id at the service; unique per source within the workspace |

Model: `src/Domain/Order/Order.php`, `OrderLine.php`, `OrderedItem.php`.

## Rules

| # | Rule | Where | Tests |
|---|---|---|---|
| O1 | A manual order, or an order imported at the day's market, always belongs to an event, and its date must fall within the event's days; an online imported order has none | `Order::__construct()` (`Event::covers()`) | `OrderTest` |
| O2 | **The event is deduced from the date** (events never overlap). No event at that date → the order is refused with « Aucun événement le … Créez d'abord l'événement » | `PlaceOrderHandler` (`EventRepository::findCovering()`) | `OrderUseCasesTest` |
| O3 | At least one line; quantities ≥ 1 | `Order`, `OrderLine`, `OrderPricing::items()` | `OrderTest`, `OrderUseCasesTest` |
| O4 | Each line is a valid (product, variant) tuple: variant mandatory for products with variants, forbidden for unique products | `Product::sellable()` via `OrderPricing` | `ProductTest`, `OrderUseCasesTest` |
| O5 | Identical tuples are merged into one line | `Order::addItem()` | `OrderTest` |
| O6 | Names/prices/costs are snapshots: editing a product never changes past orders. A line stores its **total cost**, computed when the units are taken from stock (FIFO, see [stock.md](stock.md)) | `OrderLine`, `StockKeeper` | `OrderTest`, `StockUseCasesTest` |
| O7 | Discount rules valid on the order's date are applied automatically to manual orders ([discounts](discounts.md)) | `OrderPricing::discounts()` | `OrderUseCasesTest` |
| O8 | Discounts never exceed the subtotal | `Order::applyDiscounts()` | `OrderTest` |
| O9 | `total = subtotal − discounts`; `costOfGoods = Σ line costs`; gross margin = total − cost of goods | `Order::total()`, `costOfGoods()`, `OrderView` | `OrderTest` |
| O10 | An event cannot be rescheduled if some of its orders would fall outside the new dates | `UpdateEventHandler` (`OrderRepository::countOutside()`) | `OrderUseCasesTest` |
| O11 | Imported orders keep their source and external id (unique within the workspace) so re-importing never duplicates them | `Order::imported()` | [imports.md](imports.md) |
| O12 | Every order of the workspace can be deleted at once; products and events stay. A later import brings the services' sales back | `DeleteAllOrdersHandler` | `DeleteAllOrdersTest` |

## Use cases & API

| Use case | Endpoint |
|---|---|
| `PlaceOrder` | `POST /api/orders` `{placedAt: "YYYY-MM-DDTHH:MM" (Paris time), lines: [{productId, variant, quantity}]}` → `{id, reference}` |
| `PreviewOrder` | `POST /api/orders/preview` (same body) → matching event (or null), discounts, totals — nothing saved |
| `ListOrders` | `GET /api/orders[?eventId=]` (most recent first) |
| `GetOrder` | `GET /api/orders/{id}` (lines, discounts, totals, cost of goods, margin) |
| `DeleteOrder` | `DELETE /api/orders/{id}` |
| `DeleteAllOrders` | `DELETE /api/orders` → `{deleted}` |

UI: `/orders` — order history (filter by event), grouped by **day and event** with the day's total and number of orders; each order shows its time, articles, discounts, total, payment method (Carte / Espèces) and (secondary) its reference. Side by side, the new-order form: date, product → variant (only when needed) → quantity, live preview of the matching event, and a footer pinned at the bottom with discounts, total and the save button. On save: toast, list refresh without page reload, new row highlighted. `/orders/{id}` — detail with margin and delete. `/settings` — « Zone de danger »: delete every order, behind a warning and a confirmation.
