# SumUp import

SumUp is the card terminal used at events. The import brings its **products** and **orders** into MossyTrunk.

Configuration: `SUMUP_API_KEY` (dashboard → API keys, `sup_sk_…`) and `SUMUP_MERCHANT_CODE` in `.env.local` (never committed).
Trigger: button « Importer depuis SumUp » on `/commandes` → `POST /api/sumup/import`.

Code: `src/Application/SumUp/ImportFromSumUp/ImportFromSumUpHandler.php`, `SumUpProductResolver.php`;
port `Application/SumUp/SumUpGateway`; adapters `Infrastructure/SumUp/SumUpApiGateway.php` (real API) and `FakeSumUpGateway.php` (test env, fixture `tests/Fixtures/sumup/transactions.json`).

## What is read from SumUp

- `GET /v2.1/merchants/{code}/transactions/history?statuses[]=SUCCESSFUL&types[]=PAYMENT` (all pages, `links[rel=next]`)
- `GET /v2.1/merchants/{code}/transactions?id=…` for each payment → `products[]` (`name`, `price_with_vat`, `quantity`)
- Amount paid = `amount − tip_amount`. Euros are converted to cents.

## Rules

| # | Rule | Where | Tests |
|---|---|---|---|
| S1 | Only successful payments are imported (no refunds, failed or cancelled) | `SumUpApiGateway` query filters | `SumUpApiGatewayTest` |
| S2 | **Idempotent**: a transaction whose code is already an order is skipped (unique `sumUpTransactionCode`) → re-importing never duplicates | `ImportFromSumUpHandler`, `OrderRepository::importedSumUpTransactionCodes()` | `ImportFromSumUpTest`, e2e `sumup.spec.js` |
| S3 | Each order is **auto-linked to the event covering its date** (Paris day) | `EventRepository::findCovering()` | `ImportFromSumUpTest` |
| S4 | No event at that date → the order is **not imported**. Whatever the number of such orders, the UI shows **one single error message** listing the dates and asking to create the event. Re-running the import after creating it imports them (S2 guarantees no duplicates) | `SumUpImportReport::datesWithoutEvent`, `useSumUpImport.js`, `SumUpImportProblem.vue` | `ImportFromSumUpTest`, e2e |
| S5 | Line → product: exact name of a product without variants; else `Nom - Variante` / `Nom (Variante)` matching an existing variant; else a **new product** is created: name from SumUp, selling price = SumUp unit price, **buying price 0** (unknown, editable later), reference generated like any product (`PRD-<NAME>`), no variants | `SumUpProductResolver` | `ImportFromSumUpTest` |
| S6 | Products are created even when the order itself can't be imported (no event) | `ImportFromSumUpHandler` | `ImportFromSumUpTest` |
| S7 | A line naming a product **with variants** but no recognisable variant can't form a valid (product, variant) tuple → that order is not imported and the product name is listed in the same error message | `SumUpProductResolver::resolve()` returns null | `ImportFromSumUpTest` |
| S8 | SumUp's amount is the truth: if it is below the lines' subtotal, the gap becomes a « Remise SumUp » discount. Bundle rules are **not** applied to imports | `Order::importFromSumUp()` | `OrderTest` |
| S9 | Existing products are never modified by an import (prices you edited are kept) | `SumUpProductResolver` | `ImportFromSumUpTest` |
