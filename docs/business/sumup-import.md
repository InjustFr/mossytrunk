# SumUp import

SumUp is the card terminal used at events. Its sales go through the generic [import](imports.md); this page lists what is specific to SumUp.

Configuration: « Paramètres › Services connectés › Ajouter un service › SumUp »: merchant code and API key (SumUp dashboard → API keys, `sup_sk_…`, stored encrypted).
Defaults: sales **at the day's market**, unknown items **create the product**, line prices **may be discounted**.
Trigger: « Importer depuis SumUp » on `/commandes` → `POST /api/services/sumup/import`.

Code: `src/Infrastructure/Connector/SumUp/` — `SumUpConnector`, `SumUpApiGateway` (real API), `SumUpPayloadMapper`, `FakeSumUpGateway` (test env, fixture `tests/Fixtures/sumup/transactions.json`), `ShowSumUpTransactionCommand` (`app:sumup:transaction <code> --workspace=…`).

## What is read from SumUp

- `GET /v2.1/merchants/{code}/transactions/history?statuses[]=SUCCESSFUL&types[]=PAYMENT` (all pages, `links[rel=next]`)
- `GET /v2.1/merchants/{code}/transactions?id=…` for each payment → `products[]` (`name`, `description` = variant (trimmed), `price_with_vat`, `quantity`, and `category` / `category_name` **when present** — not documented by SumUp, read defensively)
- Mapping: transaction code = external id and order reference; charged = `amount − tip_amount`; no shipping; line name = the item (lowercased as its external reference). Euros are converted to cents.

## Rules

| # | Rule | Where | Tests |
|---|---|---|---|
| S1 | Only successful payments are imported (no refunds, failed or cancelled) | `SumUpApiGateway` query filters | `SumUpApiGatewayTest` |
| S2 | Idempotent on the transaction code (see [imports.md](imports.md) I3) | `ImportSalesHandler` | `ImportSumUpSalesTest`, e2e `sumup.spec.js` |
| S5b | When SumUp gives a **category**, it becomes the created product's **type** (found case-insensitively or created), and a leading « {category} » is removed from the name: « Print Forêt » in category Print → type Print + name « Forêt » | `ImportedCatalogue::createFor()` | `ImportSumUpSalesTest`, `SumUpPayloadMapperTest` |
| S7 | A line naming a product **with variants** but no recognisable variant is remembered « à associer »; its order waits until the item is linked (see I6) | `ExternalItemResolver` | `ImportSumUpSalesTest` |
| S8 | SumUp's discount is the gap between the lines and the amount charged; the day's discount rules replace it within 2 cents (see I8) | `Order::imported()` | `OrderTest`, `ImportSumUpSalesTest` |
| S10 | A line **without a name** (an amount typed on the terminal) is sold as the « Montant libre » product (created once, no type), at the line's own SumUp price | `ExternalItemResolver`, `ImportedCatalogue::freeAmount()` | `ImportSumUpSalesTest` |
| S11 | SumUp spreads a basket discount over the lines: a line is sold at the **higher** of the product's price and SumUp's line price (see I9) | `LinePrices::MayBeDiscounted` | `ImportSumUpSalesTest` |
| S12 | A product's `description` is its **variant**: a variant of the named product; a product **split per variant** (`<name> <variant>`, `<name> - <variant>`, `<name> (<variant>)` without variants of its own); otherwise, when creating products, a new variant learnt by the named product when it has variants or was created by this import. On another existing product without variants the description is ignored | `SumUpPayloadMapper::variant()`, `ExternalItemResolver` | `SumUpPayloadMapperTest`, `ImportSumUpSalesTest` |
| S13 | The order keeps SumUp's **payment method**: `payment_type` `CASH` → « Espèces », any other value (`POS`, `ECOM`…) → « Carte »; unknown when SumUp sends none | `SumUpPayloadMapper::paymentMethod()` | `SumUpPayloadMapperTest`, `ImportSumUpSalesTest` |
