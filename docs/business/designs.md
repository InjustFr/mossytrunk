# Designs (Créations)

Products are made from **designs** (an illustration) declined onto **gabarits** — generic supports such as a
15×15 print, a glossy sticker or a mat sticker. Each support needs its own adaptations of the design.
Once every declination is ready, **validating** the design creates one product per declination.

| Concept | Fields |
|---|---|
| `Gabarit` | Name (unique per workspace, case-insensitive), product type, default selling/buying prices, default variants, **adaptations** (checklist, unique labels) |
| `DesignCollection` | Name, description, « sur l'établi » flag |
| `Design` | Name, optional collection, notes, status `in_progress` / `validated`, « sur l'établi » flag, validation date |
| `Declination` | Design × gabarit: product name (defaults to the design name), prices and variants (default to the gabarit's), its adaptations (copied from the gabarit when declined) and the ones done, the product created at validation |

Model: `src/Domain/Design/`.

## Rules

| # | Rule | Where | Tests |
|---|---|---|---|
| D1 | A design is declined at most once per gabarit; the declination copies the gabarit's prices, variants and adaptations (later gabarit changes do not alter existing declinations) | `Design::decline()`, `Declination` | `DesignTest` |
| D2 | Adaptations are ticked or unticked one by one; a declination is **ready** when none is left | `Declination::tick()`, `pendingAdaptations()` | `DesignTest` |
| D3 | A new design and a new collection are « sur l'établi » (being worked on); either can be paused or put back; a validated design leaves the bench | `Design::workOn()`, `DesignCollection::workOn()` | `DesignUseCasesTest` |
| D4 | Validating needs at least one declination, every declination ready, and distinct resulting product names | `Design::validate()` | `DesignTest`, `DesignUseCasesTest` |
| D5 | Validation creates one product per declination: type of the gabarit, the declination's name, prices and variants, a generated reference (P2); stock starts at 0 | `ValidateDesignHandler` | `DesignUseCasesTest`, `DesignApiTest` |
| D6 | A validated design is frozen: no new declination, adjustment, tick or deletion | `Design::assertInProgress()`, `DeleteDesignHandler` | `DesignTest` |
| D7 | Validating a collection validates each of its designs still in progress, all or nothing | `ValidateDesignHandler::collection()` | `DesignUseCasesTest` |

## Use cases & API

| Use case | Endpoint |
|---|---|
| `ListGabarits` / `SaveGabarit` | `GET /api/gabarits`, `POST /api/gabarits`, `PUT /api/gabarits/{id}` |
| `ListDesigns` | `GET /api/designs` (collections with their designs, and designs without collection) |
| `SaveDesign` / `GetDesign` / `DeleteDesign` | `POST /api/designs` (optionally with `gabaritIds`), `GET` / `PUT` / `DELETE /api/designs/{id}` |
| `WorkOn` | `PUT /api/designs/{id}/current`, `PUT /api/design-collections/{id}/current` `{current}` |
| `DeclineDesign` / `WithdrawDeclination` / `AdjustDeclination` | `POST /api/designs/{id}/declinations` `{gabaritId}`, `DELETE` / `PUT /api/designs/{id}/declinations/{declinationId}` |
| `TickAdaptation` | `PUT /api/designs/{id}/declinations/{declinationId}/adaptations` `{adaptation, done}` |
| `ValidateDesign` | `POST /api/designs/{id}/validation`, `POST /api/design-collections/{id}/validation` → `{productsCreated}` |
| `SaveCollection` | `POST /api/design-collections`, `PUT /api/design-collections/{id}` |

## UI

- **Créations** (`/creations`): « Sur l'établi » cards (collection, gabarits — solid when ready —, adaptation progress), then each collection (switch « Sur l'établi », its designs, « Ajouter un design », « Valider la collection » once every design is ready) and « Designs seuls ». Header: « Gabarits » (manager), « Nouvelle collection », « Nouveau design » (name, collection, gabarits to start with, notes).
- **Design** (`/creations/{id}`): « Décliner sur » buttons for the unused gabarits, one card per declination with the adaptation checklist and the product-to-be (name, prices, variants). « Valider le design » lists the products it will create.
