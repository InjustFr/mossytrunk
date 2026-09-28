# MossyTrunk — business documentation

MossyTrunk helps a small creative business that sells at **events** (conventions, markets) manage its sales.
Module 1 = **Order Management**. This folder is the reference for business rules: each page lists the rules,
**where they are modelled** in the code and **which tests** cover them. Update it in the same commit as any rule change.

## Pages

| Page | Covers |
|---|---|
| [accounts.md](accounts.md) | Users, workspaces, sign-in, invitation and password reset links |
| [products.md](products.md) | Catalogue, variants, the (product, variant) tuple, buying price 0 = unknown |
| [events.md](events.md) | Events, periods in Europe/Paris, no overlap, expenses |
| [orders.md](orders.md) | Orders, auto-link to the event, lines & snapshots, totals, margin |
| [discounts.md](discounts.md) | Bundle discounts and the automatic calculation |
| [event-report.md](event-report.md) | Profitability of an event: Dépenses, Commandes, URSSAF 12.8 %, Total |
| [dashboard.md](dashboard.md) | Results per month and per year |
| [sumup-import.md](sumup-import.md) | Importing products and orders from SumUp, idempotency, single error message |

## Glossary (UI term → code)

| UI (FR) | Code | Meaning |
|---|---|---|
| Espace de travail | `Domain\Identity\Workspace` | One business and all its data |
| Utilisateur | `Domain\Identity\User` | Person signing in with an email and password |
| Produit | `Domain\Product\Product` | Real item sold; unique reference |
| Type de produit | `Domain\Product\ProductType` | Print, Sticker…; product shown as « {type} {nom} » |
| Variante | `Product::$variants` (strings) | Colour, size, design…; none = *produit unique* |
| Article vendable | `Domain\Product\SellableItem` | Validated (product ULID, variant) tuple + prices at sale time |
| Événement | `Domain\Event\Event` | Convention/market with an inclusive day period and a location |
| Dépense | `Domain\Event\Expense` | Money spent for an event |
| Commande | `Domain\Order\Order` | A sale during an event |
| Ligne de commande | `Domain\Order\OrderLine` | Quantity of one tuple, with name/price/cost snapshots |
| Remise (par lot) | `Domain\Discount\DiscountRule` | N eligible units for a fixed price |
| Remise appliquée | `Domain\Discount\AppliedDiscount` | `{label, amount}` snapshot stored on an order |
| Sous-total / Total | `Order::subtotal()` / `total()` | Before / after discounts |
| Coût d'achat | `Order::costOfGoods()` | Σ buying price × quantity |
| Chiffre d'affaires | `EventResult::$turnover` | Σ order totals of an event |
| URSSAF | `Domain\Reporting\UrssafContribution` | 12.8 % of turnover |
| Résultat | `EventResult::$result` | Turnover − cost of goods − expenses − URSSAF |

## Cross-cutting conventions

- **Workspaces**: every product, type, event, order and discount belongs to one workspace; users only ever see and change the data of their own workspace, and uniqueness rules apply within a workspace (see [accounts.md](accounts.md)).
- **Money** is integer cents (`Domain\Shared\Money`); the API exchanges cents; the UI shows `12,50 €`.
- **Dates**: business time zone is Europe/Paris (`DateRange::TIMEZONE`). Events are whole days; orders are stored with their time zone.
- **Identifiers**: ULIDs everywhere (`symfony/uid`, stored as PostgreSQL `uuid`).
- **Business rule violations** throw `Domain\Shared\DomainException` subclasses with French user-facing messages, returned as HTTP 422 (`404` for `NotFound`).
- **Snapshots**: orders never depend on the current state of products or discount rules.
