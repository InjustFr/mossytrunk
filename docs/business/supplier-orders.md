# Supplier orders (Commandes fournisseurs)

A **supplier order** records what was bought from a **supplier** (printer, textile workshop…) and for how much,
then — once the parcel arrives — what was really received. Reception feeds the [stock](stock.md) at the **real unit cost**.

| Field | Meaning |
|---|---|
| `Supplier` | Name (unique per workspace, case-insensitive), optional contact and notes |
| `reference` | `CMF-YYYYMMDD-XXXXXX`, generated from the order date |
| `orderedOn` | Day the order was placed |
| `status` | `ordered` (« Commandée ») then `received` (« Reçue »), with `receivedAt` |
| lines | Sellable item (product, variant), ordered quantity, **total price paid** for the line, received quantity once received |

Model: `src/Domain/Purchasing/Supplier.php`, `SupplierOrder.php`, `SupplierOrderLine.php`.

## Rules

| # | Rule | Where | Tests |
|---|---|---|---|
| F1 | A supplier has a name, unique per workspace (case-insensitive) | `Supplier::describe()`, `SaveSupplierHandler` | `SupplierOrderTest`, `PurchasingUseCasesTest` |
| F2 | An order has at least one line; each sellable item appears once; quantity ≥ 1; total price ≥ 0 | `SupplierOrder::revise()`, `SupplierOrderLine` | `SupplierOrderTest` |
| F3 | Planned unit cost = total price / ordered quantity | `SupplierOrderLine::plannedUnitCost()` | `SupplierOrderTest` |
| F4 | While « Commandée », an order can be revised (supplier, date, lines) or deleted | `SupplierOrder::revise()`, `DeleteSupplierOrderHandler` | `PurchasingUseCasesTest` |
| F5 | **Reception** gives a received quantity (≥ 0) for every line — it may be more (extra prints) or less (damaged) than ordered. The order becomes « Reçue » and can no longer change | `SupplierOrder::receive()` | `SupplierOrderTest`, `PurchasingUseCasesTest` |
| F6 | Real unit cost = total price / **received** quantity. Each line with something received enters stock as a « Commande fournisseur » lot at that cost, and the product's buying price becomes it (K2) | `SupplierOrderLine::unitCost()`, `ReceiveSupplierOrderHandler` | `SupplierOrderTest`, `PurchasingUseCasesTest` |
| F7 | A line received at 0 adds no stock and has no unit cost | `SupplierOrder::receive()` | `SupplierOrderTest` |

## Use cases & API

| Use case | Endpoint |
|---|---|
| `ListSuppliers` / `SaveSupplier` | `GET /api/suppliers`, `POST /api/suppliers`, `PUT /api/suppliers/{id}` `{name, contact, notes}` |
| `ListSupplierOrders` / `GetSupplierOrder` | `GET /api/supplier-orders`, `GET /api/supplier-orders/{id}` |
| `PlaceSupplierOrder` / `ReviseSupplierOrder` | `POST /api/supplier-orders`, `PUT /api/supplier-orders/{id}` `{supplierId, orderedOn, lines: [{productId, variant, quantity, totalPrice}]}` |
| `DeleteSupplierOrder` | `DELETE /api/supplier-orders/{id}` (422 once received) |
| `ReceiveSupplierOrder` | `POST /api/supplier-orders/{id}/reception` `{lines: [{lineId, received}]}` |

## UI

- **Fournisseurs** (`/commandes-fournisseurs`): orders filtered « À réceptionner » / « Reçues » / « Toutes »; « Nouvelle commande » opens a drawer (supplier with inline creation, date, product lines with quantity and total price, unit price shown); « Fournisseurs » manages the supplier list.
- **Commande fournisseur** (`/commandes-fournisseurs/{id}`): facts and lines (ordered, received with the gap, price paid, real unit cost vs planned). While « Commandée »: « Modifier », « Supprimer », « Réceptionner ».
- **Réception** is a guided, line-by-line check: « Tout est arrivé » or a counted quantity, the gap and the recomputed unit cost update live, then a summary before « Valider la réception ».
- The product stock history links « Commande fournisseur » lots to their order.
