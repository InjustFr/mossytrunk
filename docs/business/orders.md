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
| O11 | Every order has an **internal reference** `CMD-YYYYMMDD-XXXXXX`. An imported order keeps the sales it comes from (`ImportedSale`: service, external id unique within the workspace, the service's reference, payment method) so re-importing never duplicates them | `Order::imported()`, `ImportedSale` | [imports.md](imports.md) |
| O14 | Two orders of **one customer** (e.g. paid half in cash, half by card, recorded as two SumUp sales) can be **merged**: same event and same source only. The chosen order joins the one it is merged into: its lines (merged with a line of the same item at the same price), discounts, shipping and imported sales; the earliest date is kept, the payment becomes « Mixte » when methods differ, and the absorbed order is deleted. Stock is not touched (lines keep their costs). Both sales stay linked, so a later import skips them | `Order::absorb()`, `MergeOrdersHandler`, `PaymentMethod::combined()` | `OrderTest`, `ImportSumUpSalesTest`, `OrderApiTest` |
| O13 | An imported line may have **no product** (« Produit inconnu »: an amount typed on the card terminal, see [sumup-import.md](sumup-import.md) S10). It never merges with another line, takes no stock, costs nothing and matches no discount. It can later be **linked to a product and variant** (once): it keeps its unit price, takes the product's name, and its units leave the stock like a sale on the order's date (cost from the lots, K rules). Discounts are not recomputed | `SellableItem::unknown()`, `OrderLine::identify()`, `IdentifyOrderLineHandler` | `OrderTest`, `ImportSumUpSalesTest`, `OrderApiTest` |
| O12 | Every order of the workspace can be deleted at once; products and events stay. A later import brings the services' sales back | `DeleteAllOrdersHandler` | `DeleteAllOrdersTest` |

## Use cases & API

| Use case | Endpoint |
|---|---|
| `PlaceOrder` | `POST /api/orders` `{placedAt: "YYYY-MM-DDTHH:MM" (Paris time), lines: [{productId, variant, quantity}]}` → `{id, reference}` |
| `PreviewOrder` | `POST /api/orders/preview` (same body) → matching event (or null), discounts, totals — nothing saved |
| `ListOrders` | `GET /api/orders[?eventId=]` (most recent first) |
| `GetOrder` | `GET /api/orders/{id}` (lines, discounts, totals, cost of goods, margin) |
| `DeleteOrder` | `DELETE /api/orders/{id}` |
| `MergeOrders` | `POST /api/orders/{id}/merge` `{orderId}` → 204 (the given order joins `{id}`) |
| `IdentifyOrderLine` | `PUT /api/orders/{id}/lines/{lineId}/product` `{productId, variant?}` → 204 (422 when the line already has a product) |
| `DeleteAllOrders` | `DELETE /api/orders` → `{deleted}` |

UI: `/orders` — order history (filter by event), grouped by **day and event** with the day's total and number of orders; each order shows its time, articles, discounts, total, payment method (Carte / Espèces) and (secondary) its reference. Side by side, the new-order form: date, product → variant (only when needed) → quantity, live preview of the matching event, and a footer pinned at the bottom with discounts, total and the save button. On save: toast, list refresh without page reload, new row highlighted. `/orders/{id}` — detail with margin, the service's references, « Fusionner avec… » (orders of the same event and source, closest in time first) and delete; a line without product is a warning badge « Produit inconnu » with « Choisir le produit » (product and variant, in a modal). `/settings` — « Zone de danger »: delete every order, behind a warning and a confirmation.
