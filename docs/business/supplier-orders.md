# Supplier orders (Commandes fournisseurs)

A **supplier order** records what was bought from a **supplier** (printer, textile workshop…) and for how much,
then — once the parcel arrives — what was really received. Reception feeds the [stock](stock.md) at the **real unit cost**.

| Field | Meaning |
|---|---|
| `Supplier` | Name (unique per workspace, case-insensitive), optional contact and notes |
| `reference` | `CMF-YYYYMMDD-XXXXXX`, generated from the order date |
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
| F4 | While « Commandée », an order can be revised (supplier, date, lines) or deleted | `SupplierOrder::revise()`, `DeleteSupplierOrderHandler` | `PurchasingUseCasesTest` |
| F5 | **Reception** gives a received quantity (≥ 0) for every line — it may be more (extra prints) or less (damaged) than ordered. The order becomes « Reçue » and can no longer change | `SupplierOrder::receive()` | `SupplierOrderTest`, `PurchasingUseCasesTest` |
| F6 | Real unit cost = line cost / **received** quantity. Each line with something received enters stock as a « Commande fournisseur » lot at that cost, and the product's buying price becomes it (K2) | `SupplierOrderLine::unitCost()`, `ReceiveSupplierOrderHandler` | `SupplierOrderTest`, `PurchasingUseCasesTest` |
| F7 | A line received at 0 adds no stock and has no unit cost | `SupplierOrder::receive()` | `SupplierOrderTest` |

## Use cases & API

| Use case | Endpoint |
|---|---|
| `ListSuppliers` / `SaveSupplier` | `GET /api/suppliers`, `POST /api/suppliers`, `PUT /api/suppliers/{id}` `{name, contact, notes}` |
| `ListSupplierOrders` / `GetSupplierOrder` | `GET /api/supplier-orders`, `GET /api/supplier-orders/{id}` |
| `PlaceSupplierOrder` / `ReviseSupplierOrder` | `POST /api/supplier-orders`, `PUT /api/supplier-orders/{id}` `{supplierId, orderedOn, discount, deliveryFees, lines: [{productId, variant, quantity, totalPrice}]}` |
| `DeleteSupplierOrder` | `DELETE /api/supplier-orders/{id}` (422 once received) |
| `ReceiveSupplierOrder` | `POST /api/supplier-orders/{id}/reception` `{lines: [{lineId, received}]}` |

## UI

- **Fournisseurs** (`/commandes-fournisseurs`): orders filtered « À réceptionner » / « Reçues » / « Toutes »; « Nouvelle commande » opens a drawer (supplier with inline creation, date, product lines with quantity and total price, « Remise globale » and « Frais de livraison », each line's resulting unit cost shown live); « Fournisseurs » manages the supplier list.
- **Commande fournisseur** (`/commandes-fournisseurs/{id}`): facts and lines (ordered, received with the gap, price paid, real unit cost vs planned). While « Commandée »: « Modifier », « Supprimer », « Déballer le colis ».
- **Déballage** (reception) is a guided, line-by-line check: « Tout est arrivé » or a counted quantity, the gap and the recomputed unit cost update live, then a summary before « Valider le déballage ».
- The product stock history links « Commande fournisseur » lots to their order.
