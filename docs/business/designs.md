# Designs (Créations)

Products are made from **designs** (an illustration) declined onto **gabarits** — generic supports such as a
15×15 print, a glossy sticker or a mat sticker. Each support needs its own adaptations of the design.
Once every declination is ready, **validating** the design creates one product per declination.

| Concept | Fields |
|---|---|
| `Gabarit` | Name (unique per workspace, case-insensitive), product type, default selling price, default variants, **adaptations** (checklist, unique labels) |
| `DesignCollection` | Name, description, « sur l'établi » flag |
| `Design` | Name, optional collection, notes, status `in_progress` / `validated`, « sur l'établi » flag, validation date |
| `Declination` | Design × gabarit: product name (defaults to the design name), selling price and variants (default to the gabarit's), its adaptations (copied from the gabarit when declined) and the ones done, the product created at validation |

Model: `src/Domain/Design/`.

## Rules

| # | Rule | Where | Tests |
|---|---|---|---|
| D1 | A design is declined at most once per gabarit; the declination copies the gabarit's selling price, variants and adaptations (later gabarit changes do not alter existing declinations) | `Design::decline()`, `Declination` | `DesignTest` |
| D2 | Adaptations are ticked or unticked one by one; a declination is **ready** when none is left | `Declination::tick()`, `pendingAdaptations()` | `DesignTest` |
| D3 | A new design and a new collection (« collection », `DesignCollection`) are « sur l'établi » (being worked on); either can be set aside (« mis de côté ») or put back; a validated design leaves the bench | `Design::workOn()`, `DesignCollection::workOn()` | `DesignUseCasesTest` |
| D4 | Validating needs at least one declination, every declination ready, and distinct resulting product names | `Design::validate()` | `DesignTest`, `DesignUseCasesTest` |
| D5 | Validation creates one product per declination: type of the gabarit, the declination's name, selling price and variants, a generated reference (P2); stock and buying price start at 0 (P3) | `ValidateDesignHandler` | `DesignUseCasesTest`, `DesignApiTest` |
| D6 | A declination that became a product is frozen (no adjustment, tick or withdrawal). The design itself stays open: declining it on a new gabarit puts it back « en cours » and on the bench, and validating again creates only the new products. A design with products cannot be deleted | `Declination::assertEditable()`, `Design::decline()`, `Design::validate()`, `DeleteDesignHandler` | `DesignTest`, `DesignUseCasesTest` |
| D7 | Validating a collection validates each of its designs still in progress, all or nothing | `ValidateCollectionHandler` | `DesignUseCasesTest` |
| D8 | An **existing product** can get its finished design (named after the product, validated, off the bench) or join an existing design: it becomes a declination on the chosen gabarit with its name, price and variants, all adaptations done. A product belongs to at most one design | `Design::fromProduct()`, `Design::adopt()`, `DesignProductHandler`, `AttachProductToDesignHandler` | `DesignTest`, `DesignUseCasesTest` |

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

- **Créations** (`/designs`): « Sur l'établi » cards (collection, gabarits — solid when ready —, adaptation progress), then each collection (switch « Sur l'établi », its designs, « Ajouter un design », « Sortir la collection de l'atelier » once every design is ready) and « Sans collection ». Header: « Gabarits » (manager), « Nouvelle collection », « Nouveau design » (name, collection, gabarits to start with, notes).
- **Design** (`/designs/{id}`): « Décliner sur » buttons for the unused gabarits (also on validated designs), one card per declination with the adaptation checklist and the product-to-be (name, selling price, variants). « Sortir de l'atelier » (« Créer les nouveaux produits » once it has products) lists the products it will create; produced declinations link to their product.
