# Supplier orders (Commandes fournisseurs)

A **supplier order** records what was bought from a **supplier** (printer, textile workshop…) and for how much,
then — once the parcel arrives — what was really received. Reception feeds the [stock](stock.md) at the **real unit cost**.

| Field | Meaning |
|---|---|
| `Supplier` | Name (unique per workspace, case-insensitive), optional contact and notes |
| `reference` | Written with the workspace's supplier order format ([references.md](references.md), `CMF-YYYYMMDD-XXXXXX` from the order date by default) |
| `supplierReference` | « Référence fournisseur », optional: the order number at the supplier (trimmed, ≤ 100 characters), changeable at any time |
| `orderedOn` | Day the order was placed |
| `status` | `ordered` (« Commandée ») then `received` (« Reçue »), with `receivedAt` |
| `discount` | « Remise globale » on the whole order, shared between the lines **in proportion to their price** |
| `deliveryFees` | « Frais de livraison », shared **equally between the lines** (5 € over five lines = 1 € each) |
| lines | Sellable item (product, variant), ordered quantity, **total price** of the line, its shares of discount and fees, received quantity once received |

Model: `src/Domain/Purchasing/Supplier.php`, `SupplierOrder.php`, `SupplierOrderLine.php`.

## Rules

| # | Rule | Where | Tests |
|---|---|---|---|
| F1 | A supplier has a name, unique per workspace (case-insensitive) | `Supplier::describe()`, `SaveSupplierHandler` | `SupplierOrderTest`, `PurchasingUseCasesTest` |
| F2 | An order has at least one line; each sellable item appears once; quantity ≥ 1; total price ≥ 0 | `SupplierOrder::revise()`, `SupplierOrderLine` | `SupplierOrderTest` |
| F3 | A line's **cost** = its price − its share of the global discount + its share of the delivery fees; shares add up to the exact amounts (leftover cents go to the largest remainders / first lines). The global discount cannot exceed the lines' prices. Order total = Σ prices − discount + fees. Planned unit cost = cost / ordered quantity | `SupplierOrder::allocate()`, `CostAllocation`, `SupplierOrderLine::landedCost()` | `SupplierOrderTest`, `CostAllocationTest` |
| F4 | An order can be revised (supplier, date, lines, discount, fees) at any time; it can be deleted only while « Commandée » | `SupplierOrder::revise()`, `DeleteSupplierOrderHandler` | `PurchasingUseCasesTest` |
| F5 | **Reception** gives a received quantity (≥ 0) for every line — it may be more (extra prints) or less (damaged) than ordered. The order becomes « Reçue » | `SupplierOrder::receive()` | `SupplierOrderTest`, `PurchasingUseCasesTest` |
| F6 | Real unit cost = line cost / **received** quantity. Each line with something received enters stock as a « Commande fournisseur » lot at that cost, and the product's buying price becomes it (K2) | `SupplierOrderLine::unitCost()`, `ReceiveSupplierOrderHandler` | `SupplierOrderTest`, `PurchasingUseCasesTest` |
| F7 | A line received at 0 adds no stock and has no unit cost | `SupplierOrder::receive()` | `SupplierOrderTest` |
| F8 | A **received** order stays editable: every line then needs its received quantity. Its stock follows: each line's « Commande fournisseur » lot is restated to the new received quantity and landed cost (units already sold stay sold, their cost is not recomputed; a line removed or received at 0 takes its units back out of stock), and the product's buying price follows when that lot is its latest purchase | `SupplierOrder::revise()`, `SupplierOrderStock::follow()`, `StockItem::restate()` | `PurchasingUseCasesTest`, `StockItemTest` |
| F9 | Two orders of the **same supplier** and the **same status** can be **merged**: lines of the same item add up (ordered and received quantities, prices), other lines are appended, discounts and delivery fees add up and are shared again (F3), the earliest order and reception dates are kept, supplier references are joined. The merged order disappears; when received, its stock lots join the kept order's | `SupplierOrder::absorb()`, `MergeSupplierOrdersHandler`, `StockItem::moveLotsOf()` | `PurchasingUseCasesTest`, `supplier-orders.spec.js` |

## Use cases & API

| Use case | Endpoint |
|---|---|
| `ListSuppliers` / `SaveSupplier` | `GET /api/suppliers`, `POST /api/suppliers`, `PUT /api/suppliers/{id}` `{name, contact, notes}` |
| `ListSupplierOrders` / `GetSupplierOrder` | `GET /api/supplier-orders`, `GET /api/supplier-orders/{id}` |
| `PlaceSupplierOrder` / `ReviseSupplierOrder` | `POST /api/supplier-orders`, `PUT /api/supplier-orders/{id}` `{supplierId, orderedOn, supplierReference?, discount, deliveryFees, lines: [{productId, variant, quantity, totalPrice, received?}]}` (`received` required on a received order) |
| `DeleteSupplierOrder` | `DELETE /api/supplier-orders/{id}` (422 once received) |
| `ReceiveSupplierOrder` | `POST /api/supplier-orders/{id}/reception` `{lines: [{lineId, received}]}` |
| `MergeSupplierOrders` | `POST /api/supplier-orders/{id}/merge` `{orderId}` (the given order joins `{id}`) |

## UI

- **Fournisseurs** (`/supplier-orders`): orders filtered « À réceptionner » / « Reçues » / « Toutes »; « Nouvelle commande » opens a drawer (supplier with inline creation, date, « Référence fournisseur », product lines with quantity and total price — added « Un produit » at a time or « Tout un type » at once: every non-archived product of the type, one line per variant, optionally only some variants, same quantity and unit price for each; archived products and variants and the imports' free-amount product are not offered; « Nouveau produit » creates an article or a supply on the spot and selects it — « Remise globale » and « Frais de livraison », each line's resulting unit cost shown live); « Fournisseurs » manages the supplier list.
- The list shows the supplier reference under the order reference.
- **Commande fournisseur** (`/supplier-orders/{id}`): facts (supplier reference included) and lines (ordered, received with the gap, price paid, real unit cost vs planned). « Modifier » (with a « Reçu » quantity per line once received), « Fusionner » (another order of the same supplier and status). While « Commandée »: « Supprimer », « Déballer le colis ».
- **Déballage** (reception) is a guided, line-by-line check: « Tout est arrivé » or a counted quantity, the gap and the recomputed unit cost update live, then a summary before « Valider le déballage ».
- The product stock history links « Commande fournisseur » lots to their order.
