# Stock

Every **sellable item** — a (product, variant) tuple, or the product alone when it has no variants — has its own stock.
Stock is made of **lots**: units received together at one cost. Sales always take units from the **oldest lot first** (FIFO),
so the cost of what was sold is what those units really cost.

| Field | Meaning |
|---|---|
| `onHand` | Units in stock. **May go negative** (units sold before being recorded in stock): the UI shows a « Négatif » badge |
| `lots` | Received units: quantity, remaining, total cost, received date, origin (Achat, Commande fournisseur, Inventaire, Retour de commande) |
| `lastUnitCost` | Unit cost of the last purchase (Achat or Commande fournisseur) |
| `Product::lowStockThreshold` | « Alerte stock bas », per product, default 10, ≥ 0 |

Model: `src/Domain/Stock/StockItem.php`, `StockLot.php`, `StockCheck.php`, `StockCheckLine.php`; application service `src/Application/Stock/StockKeeper.php`.

## Rules

| # | Rule | Where | Tests |
|---|---|---|---|
| K1 | One stock per sellable item (validated by `Product::sellable()`); it belongs to the product's workspace and disappears with the product or when its variant is removed | `StockItem::open()`, `StockKeeper::forgetUnsold()`, FK `ON DELETE CASCADE` | `StockItemTest`, `StockUseCasesTest` |
| K2 | **Restocking** (by hand, or by receiving a [supplier order](supplier-orders.md)) adds a lot: quantity ≥ 1, total paid ≥ 0; unit cost = total / quantity. The product's buying price becomes that unit cost (last purchase price) | `StockItem::receive()`, `RestockHandler`, `Product::bought()` | `StockItemTest`, `StockUseCasesTest` |
| K3 | Sales take units from the lot received first (then by id). A lot's unit costs always add up to exactly what was paid for it (cents spread by rounding the running total) | `StockItem::withdraw()`, `StockLot::take()` | `StockItemTest` |
| K4 | Selling more than the stock is allowed: `onHand` goes negative and the missing units cost the **last purchase price**, or the product's buying price if it was never bought | `StockItem::withdraw()` | `StockItemTest`, `StockUseCasesTest` |
| K5 | A lot received while the stock is negative first covers the units already sold (they are not charged twice) | `StockItem::receive()` | `StockItemTest` |
| K6 | Placing an order (manual or imported) takes its units from stock and stores the resulting **total cost on each order line**; imported sales are taken oldest first | `StockKeeper::withdraw()`, `PlaceOrderHandler`, `ImportSalesHandler` | `StockUseCasesTest`, `ImportSumUpSalesTest` |
| K7 | Deleting an order (or every order) puts its units back as a « Retour de commande » lot at their sale cost, dated at the sale — so they are sold again first | `StockKeeper::putBack()`, `DeleteOrderHandler`, `DeleteAllOrdersHandler` | `StockItemTest`, `StockUseCasesTest` |
| K8 | Moving a variant moves its stock (lots and balance) to the target item | `StockKeeper::move()`, `StockItem::absorb()` | `StockItemTest`, `StockUseCasesTest` |
| K9 | **Stock bas** when `onHand ≤ threshold` (per variant; the product is flagged when any variant is). **Négatif** when `onHand < 0` | `StockItem::isLowAt()`, `ProductStock` | `StockItemTest`, `StockUseCasesTest` |
| K10 | The product's **stock cost** is the average unit cost of the units left in stock, or its buying price when nothing is left; the product list's margin uses it | `ProductStock::of()` | `StockUseCasesTest` |

## Inventory after an event (Inventaire)

| # | Rule | Where | Tests |
|---|---|---|---|
| K11 | An inventory belongs to an event and counts at least one item, each at most once; counts are ≥ 0. Expected = `onHand` when counting | `StockCheck::take()` | `StockCheckTest` |
| K12 | Counting corrects the stock at once: fewer units are withdrawn FIFO (their cost is the loss), more units are added as an « Inventaire » lot at the next unit cost | `StockItem::correctTo()` | `StockItemTest` |
| K13 | Missing units are **unexplained**: the event shows « Commande manquante probable » with the units and the sales they represent (units × selling price) | `StockCheckLine::unexplained()`, `StockCheckView`, `ListEventsHandler` | `StockCheckTest`, `StockUseCasesTest` |
| K14 | An order later added to that event first **explains** those units instead of taking them from stock again; their cost is the loss recorded by the inventory | `StockCheck::explain()`, `StockKeeper::withdraw()` | `StockCheckTest`, `StockUseCasesTest` |
| K15 | « Classer l'écart » (breakage, loss, gift) clears what remains unexplained on a line; surplus lines are informational | `StockCheck::dismiss()` | `StockCheckTest`, `StockUseCasesTest` |

## Use cases & API

| Use case | Endpoint |
|---|---|
| `Restock` | `POST /api/stock/restock` `{productId, variant, quantity, totalPaid}` |
| `GetProductStock` | `GET /api/products/{id}/stock` (per item: onHand, low, negative, lots newest first) |
| `GetStockSheet` | `GET /api/events/{id}/stock-sheet` (every sellable item with its stock and what the event sold) |
| `TakeStockCheck` | `POST /api/events/{id}/stock-checks` `{items: [{productId, variant, counted}]}` |
| `ListStockChecks` | `GET /api/events/{id}/stock-checks` |
| `DismissDiscrepancy` | `POST /api/stock-checks/{id}/lines/{lineId}/dismissal` |

## UI

- **Produits**: « Réserve » column with « Stock bas » / « Négatif » badges (variant detail in the tooltip), « Coût » = stock cost, filter « produits en stock bas » (`?stock=bas`), row actions « Réapprovisionner » and « Historique de la réserve » (lots strip, oldest first, and lot table). The product form has « Alerte stock bas ».
- **Événement**: « Faire l'inventaire » (past or ongoing events) opens `/events/{id}/stock-check`; a warning banner lists unexplained units with « Classer l'écart ». The events comparison table shows a warning icon.
- **Carnet de bord**: a notice counts products in low or negative stock.
