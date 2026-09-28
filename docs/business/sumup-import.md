# SumUp import

SumUp is the card terminal used at events. The import brings its **products** and **orders** into MossyTrunk.

Configuration: per workspace, on the « Paramètres » page (`/parametres`): merchant code and API key (SumUp dashboard → API keys, `sup_sk_…`). The key is stored encrypted and never shown again (see [accounts.md](accounts.md), A11). Without them the import answers « SumUp n'est pas configuré ».
Trigger: button « Importer depuis SumUp » on `/commandes` → `POST /api/sumup/import`.

Code: `src/Application/SumUp/ImportFromSumUp/ImportFromSumUpHandler.php`, `SumUpProductResolver.php`;
port `Application/SumUp/SumUpGateway`; adapters `Infrastructure/SumUp/SumUpApiGateway.php` (real API) and `FakeSumUpGateway.php` (test env, fixture `tests/Fixtures/sumup/transactions.json`).

## What is read from SumUp

- `GET /v2.1/merchants/{code}/transactions/history?statuses[]=SUCCESSFUL&types[]=PAYMENT` (all pages, `links[rel=next]`)
- `GET /v2.1/merchants/{code}/transactions?id=…` for each payment → `products[]` (`name`, `price_with_vat`, `quantity`, and `category` / `category_name` **when present** — not documented by SumUp, read defensively)
- Amount paid = `amount − tip_amount`. Euros are converted to cents.

## Rules

| # | Rule | Where | Tests |
|---|---|---|---|
| S1 | Only successful payments are imported (no refunds, failed or cancelled) | `SumUpApiGateway` query filters | `SumUpApiGatewayTest` |
| S2 | **Idempotent**: a transaction whose code is already an order is skipped (`sumUpTransactionCode` unique within the workspace) → re-importing never duplicates | `ImportFromSumUpHandler`, `OrderRepository::importedSumUpTransactionCodes()` | `ImportFromSumUpTest`, e2e `sumup.spec.js` |
| S3 | Each order is **auto-linked to the event covering its date** (Paris day) | `EventRepository::findCovering()` | `ImportFromSumUpTest` |
| S4 | No event at that date → the order is **not imported**. Whatever the number of such orders, the UI shows **one single error message** listing the dates and asking to create the event. Re-running the import after creating it imports them (S2 guarantees no duplicates) | `SumUpImportReport::datesWithoutEvent`, `useSumUpImport.js`, `SumUpImportProblem.vue` | `ImportFromSumUpTest`, e2e |
| S5 | Line → product, comparing with **display names** (« Print Forêt », case-insensitive): a product without variants displayed like the line; else `Nom - Variante` / `Nom (Variante)` matching an existing variant; else a **new product** is created: selling price = SumUp unit price, **buying price 0** (unknown, editable later), generated reference, no variants | `SumUpProductResolver` | `ImportFromSumUpTest` |
| S5b | When SumUp gives a **category**, it becomes the new product's **type** (found case-insensitively or created), and a leading « {category} » is removed from the name: « Print Forêt » in category Print → type Print + name « Forêt » | `SumUpProductResolver::type()`, `withoutPrefix()` | `ImportFromSumUpTest`, `SumUpPayloadMapperTest` |
| S6 | Products are created even when the order itself can't be imported (no event) | `ImportFromSumUpHandler` | `ImportFromSumUpTest` |
| S7 | A line naming a product **with variants** but no recognisable variant can't form a valid (product, variant) tuple → that order is not imported and the product name is listed in the same error message | `SumUpProductResolver::resolve()` returns null | `ImportFromSumUpTest` |
| S8 | SumUp's amount is the truth: if it is below the lines' subtotal, the gap becomes a « Remise SumUp » discount. Bundle rules are **not** applied to imports | `Order::importFromSumUp()` | `OrderTest` |
| S9 | Existing products are never modified by an import (prices you edited are kept) | `SumUpProductResolver` | `ImportFromSumUpTest` |
| S11 | SumUp spreads a basket discount over the lines, so a line can be cheaper than the product. An order line is sold at the **higher** of the product's price and SumUp's line price (the difference becomes the « Remise SumUp », S8), so the order total never drops below what SumUp charged because of a low catalogue price. A product **created by the import** takes the highest price SumUp charged for it during that import, not the (possibly discounted) first one | `SumUpProductResolver::sold()` | `ImportFromSumUpTest` |
| S10 | A line **without a name** (an amount typed on the terminal) is sold as the « Montant libre » product (created once, no type), at the line's own SumUp price rather than the product's | `SumUpProductResolver::freeAmount()`, `SellableItem::at()` | `ImportFromSumUpTest` |
