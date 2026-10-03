# Sales notebook (Carnet de ventes)

At the stall every sale is also written in a paper notebook, so that nothing is forgotten. After the event, the user
photographs each page; the app reads the sales written down and compares them **one by one** with the event's orders
(imported from SumUp or entered by hand). Neither side is trusted: the notebook can miss a sale or hold a mistake, and so can
SumUp. Equal totals prove nothing — one sale missing on each side still gives the same count — so the report pairs sales
individually.

Model: `src/Domain/Notebook/NotebookScan.php` (the reading, stored per event), `NotebookEntry.php` / `NotebookLine.php`
(a sale written down and its items), `NotebookReconciliation.php` + `LineComparison.php` (pairing), `RecordedOrder.php` /
`RecordedLine.php` (the event's orders, read by `Infrastructure/Persistence/Doctrine/Notebook/DoctrineRecordedOrders.php`);
reading port `Application\Notebook\NotebookReader`, implemented by `Infrastructure\Notebook\ClaudeNotebookReader`
(Claude vision, structured output) and `FakeNotebookReader` in `test` (fixture `tests/Fixtures/notebook/entries.json`).

## Rules

| # | Rule | Where | Tests |
|---|---|---|---|
| N1 | Photos are read in the order given; each sale written down becomes an entry (page + items with quantity ≥ 1, as written). Order numbers written in the notebook are ignored: they only help tell sales apart. Prices, totals, payment methods and crossed-out sales are ignored | `ClaudeNotebookReader::INSTRUCTIONS`, `NotebookAnswer` | `NotebookAnswerTest` |
| N2 | Each item is matched to the catalogue (articles only) when recognised: a **product** (with its variant if written and offered, its type kept), else a **product type** (« 2 stickers »), else it stays unrecognised (« Non reconnu ») | `NotebookCatalogue::line()` | `NotebookCatalogueTest` |
| N3 | A reading holds at least one sale; reading an event's notebook again **replaces** the previous reading | `NotebookScan::read()`, `reread()` | `NotebookScanTest`, `NotebookUseCasesTest` |
| N4 | A notebook item covers sold units of the same product (any variant when none is written, else the same variant), or of any product of the same type when only the type is written. Unrecognised items cover nothing | `NotebookLine::accepts()`, `LineComparison::of()` | `NotebookReconciliationTest` |
| N5 | Similarity of a sale and an order = (2 × units covered + units of the same product in another variant) / (units written + units sold). They can be paired from 0.5 | `LineComparison::similarity()`, `NotebookReconciliation::PAIRING_THRESHOLD` | `NotebookReconciliationTest` |
| N6 | Pairs are chosen most similar first; between equally similar ones, the closest in sequence (relative position in the notebook vs. in the day's orders) wins. Each sale and each order is paired at most once | `NotebookReconciliation::between()` | `NotebookReconciliationTest` |
| N7 | The report lists: sales **absent from the app** (unpaired entries), orders **absent from the notebook** (unpaired orders), sales **with differences** (pairs, with what was only written and only sold) and **matching** sales. Refunded orders are compared too and flagged | `Reconciliation`, `NotebookReconciliationView` | `NotebookUseCasesTest`, `NotebookApiTest` |
| N8 | The report is recomputed from the stored reading against the current orders: adding a forgotten order updates it without a new scan | `GetNotebookReconciliationHandler` | `NotebookUseCasesTest` |

## Use cases & API

| Use case | Endpoint |
|---|---|
| `ScanNotebook` | `POST /api/events/{id}/notebook` multipart `pages[]` (1 to 30 JPEG/PNG/WebP/GIF images, 5 MB max each; the browser downsizes photos to 2000 px JPEG first) |
| `GetNotebookReconciliation` | `GET /api/events/{id}/notebook` → `null` before any scan, else `{scannedAt, pages, summary, notRecorded, notNoted, differing, matching}` |

Reading needs `ANTHROPIC_API_KEY` on the server (`.env`, `deploy/.env.dist`); without it the scan answers 422. A scan is a
synchronous call to Claude (`claude-opus-5-5`, effort high, server-side refusal fallback) and can take a minute or two.

## UI

`/events/{id}/notebook` (button « Comparer le carnet » on the event): photo picker with thumbnails, « Analyser le carnet »,
then the report — counters, « Notées dans le carnet, absentes de l'app », « Dans l'app, absentes du carnet », « Ventes avec
écart », « Ventes concordantes » (collapsed); « Nouvelle analyse » scans again.
