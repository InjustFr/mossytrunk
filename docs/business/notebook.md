# Sales notebook (Carnet de ventes)

At the stall every sale is also written in a paper notebook, so that nothing is forgotten. After the event, the user
photographs each page; the app reads the handwriting, splits it into sales following the workspace's **notebook template**,
and compares them **one by one** with the event's orders (imported from SumUp or entered by hand). Neither side is trusted:
the notebook can miss a sale or hold a mistake, and so can SumUp. Equal totals prove nothing — one sale missing on each side
still gives the same count — so the report pairs sales individually.

No LLM is involved: handwriting is read by **Kraken** (open-source HTR, model McCATMuS for modern Latin-script handwriting,
French included) running in the self-hosted `htr` service (`docker/htr`), and everything after that is deterministic rules.

Model: `src/Domain/Notebook/` — `NotebookTemplate` (separation + abbreviations, per workspace), `SaleSeparation` and its
`SaleBoundaries` (`NumberedSales`, `OneSalePerLine`, `GapSeparatedSales`), `ItemParser`, `RecognizedPage` (gap detection),
`Words` (word comparison), `NotebookScan` (the reading, stored per event), `NotebookEntry` / `NotebookLine`,
`NotebookReconciliation` + `LineComparison` (pairing), `RecordedOrder` / `RecordedLine` (the event's orders, read by
`Infrastructure/Persistence/Doctrine/Notebook/DoctrineRecordedOrders.php`); catalogue matching
`Application\Notebook\NotebookCatalogue`; recognition port `Application\Notebook\HandwritingRecognizer`, implemented by
`Infrastructure\Notebook\KrakenRecognizer` (HTTP to `HTR_URL`) and `FakeHandwritingRecognizer` in `test`
(fixture `tests/Fixtures/notebook/page.txt`).

## Rules

| # | Rule | Where | Tests |
|---|---|---|---|
| N1 | Each photo is read on its own into lines of text, top to bottom; a gap wider than 1.6 × the page's usual line spacing becomes a blank line. The text is shown and **can be corrected** (or a page typed) before the analysis | `RecognizedPage::text()`, `RecognizeNotebookPageHandler` | `RecognizedPageTest`, `NotebookUseCasesTest` |
| N2 | The workspace's template says how sales are separated: **numbered** (default: a line starting with a number followed by `)`, `.`, `-`, `:`, `/` — optionally `#`/`n°` — starts a sale, following lines belong to it, lines before a page's first number are ignored), **one per line**, or **gap** (a blank line or a rule `----` separates sales). Order numbers are never kept | `NotebookTemplate::sales()`, `SaleBoundaries` | `NotebookTemplateTest` |
| N3 | A sale's items are separated by `,` `;` `+` or new lines. Quantity: `2 x`, `2x`, `x2` before, `x 2` after, default 1. Prices (`12€`, `12,50 €`, a bare trailing number), payment words (CB, espèces, chèque, SumUp…) and totals are dropped; a sale with no item left is skipped | `ItemParser` | `ItemParserTest` |
| N4 | Abbreviations of the template are replaced (whole word, case ignored) before matching; each is defined once | `Abbreviation`, `NotebookTemplate::configure()`, `expand()` | `NotebookTemplateTest` |
| N5 | An item names the **product** whose name words it best covers, each word weighted by its rarity in the catalogue (a word shared by several products counts less), from 50 % covered; accents, plurals and small reading mistakes (1 letter from 4 letters, 2 from 7) are tolerated; mentioning the type breaks ties. A variant written with it is kept. Otherwise the **type** whose name it contains, else it stays unrecognised (« Non reconnu »). Articles only | `NotebookCatalogue::line()`, `Words` | `NotebookCatalogueTest` |
| N6 | A reading holds at least one sale; reading an event's notebook again **replaces** the previous reading | `NotebookScan::read()`, `reread()` | `NotebookScanTest`, `NotebookUseCasesTest` |
| N7 | A notebook item covers sold units of the same product (any variant when none is written, else the same variant), or of any product of the same type when only the type is written. Unrecognised items cover nothing | `NotebookLine::accepts()`, `LineComparison::of()` | `NotebookReconciliationTest` |
| N8 | Similarity of a sale and an order = (2 × units covered + units of the same product in another variant) / (units written + units sold). They can be paired from 0.5 | `LineComparison::similarity()`, `NotebookReconciliation::PAIRING_THRESHOLD` | `NotebookReconciliationTest` |
| N9 | Pairs are chosen most similar first; between equally similar ones, the closest in sequence (relative position in the notebook vs. in the day's orders) wins. Each sale and each order is paired at most once | `NotebookReconciliation::between()` | `NotebookReconciliationTest` |
| N10 | The report lists: sales **absent from the app**, orders **absent from the notebook**, sales **with differences** (what was only written and only sold) and **matching** sales. Refunded orders are compared too and flagged | `Reconciliation`, `NotebookReconciliationView` | `NotebookUseCasesTest`, `NotebookApiTest` |
| N11 | The report is recomputed from the stored reading against the current orders: adding a forgotten order updates it without a new scan | `GetNotebookReconciliationHandler` | `NotebookUseCasesTest` |

## Use cases & API

| Use case | Endpoint |
|---|---|
| `RecognizeNotebookPage` | `POST /api/notebook/recognitions` multipart `photo` (JPEG/PNG/WebP, 10 MB max; the browser downsizes to 2400 px JPEG first) → `{text}`; one page per call (~15 s on CPU) |
| `ScanNotebook` | `POST /api/events/{id}/notebook` `{pages: [text, …]}` (1 to 30 pages) |
| `GetNotebookReconciliation` | `GET /api/events/{id}/notebook` → `null` before any scan, else `{scannedAt, pages, summary, notRecorded, notNoted, differing, matching}` |
| `GetNotebookTemplate` | `GET /api/notebook-template` → `{separation: numbered\|line\|gap, abbreviations: [{short, full}]}` (numbered and none until configured) |
| `ConfigureNotebookTemplate` | `PUT /api/notebook-template` (same body) |

## UI

- `/events/{id}/notebook` (button « Comparer le carnet » on the event): « Ajouter des photos » reads each photo in turn and shows
  its text in an editable box next to the thumbnail; « Taper une page » adds a page without photo; « Analyser le carnet », then
  the report — counters, « Notées dans le carnet, absentes de l'app », « Dans l'app, absentes du carnet », « Ventes avec écart »,
  « Ventes concordantes » (collapsed); « Nouvelle analyse » scans again.
- **Paramètres › Carnet de ventes**: sale separation and abbreviations.
