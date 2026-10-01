# References (Références)

Orders, supplier orders and products each carry a **reference**, unique within the workspace. The workspace chooses
how they are written in **Paramètres › Références**: one **format** per kind of item, mixing its own text with **tags**
replaced at each creation.

| Kind | Default format (until changed) | Moment read by the date and time tags |
|---|---|---|
| Commandes (`order`) | `CMD-{date}-{random}` → `CMD-20261001-7K3QZP` | the order's date and time (`placedAt`), imported orders included |
| Commandes fournisseur (`supplier_order`) | `CMF-{date}-{random}` → `CMF-20261001-M2A9TB` | the order day (`orderedOn`) |
| Produits (`product`) | `{type}-{name}` → `PRI-FOR` | the creation day |

Model: `src/Domain/Reference/` — `ReferenceFormat` (per workspace and kind: template + next number), `ReferenceTemplate`
(parsed template), `ReferenceToken`, `ReferenceKind`, `ReferenceSubject` (what tags read); `Order`, `SupplierOrder` and
`Product` implement `Referenced`.

## Tags

| Tag | Gives | Kinds | Size (`{tag:N}`) |
|---|---|---|---|
| `{date}` | `20261001` (Europe/Paris) | all | — |
| `{year}` / `{month}` / `{day}` | `2026` / `10` / `01` | all | year: 2–4 last digits (`{year:2}` → `26`) |
| `{time}` / `{hour}` / `{minute}` | `1530` / `15` / `30` | orders | — |
| `{number}` | increasing number of the workspace for that kind (1, 2, 3…) | all | 1–10, zero-padded (`{number:4}` → `0042`; more digits once it grows past) |
| `{random}` | random uppercase letters and digits (no I, L, O, U) | all | 2–12, default 6 |
| `{type}` | the type code (`PRI`, `PRD` without type) | products | — |
| `{name}` | first three letters/digits of the name, accents removed (`FOR`, `X` when none) | products | — |

## Rules

| # | Rule | Where | Tests |
|---|---|---|---|
| R1 | A format is not blank (trimmed); outside tags it holds only letters, digits and `- _ . / #`; a tag must exist **for that kind** and take a size only when the table allows it, within its range; a format whose longest possible reference exceeds **56 characters** is refused (room left for the unicity suffix within 64) | `ReferenceTemplate::of()`, `ReferencePlaceholder::of()` | `ReferenceTemplateTest`, `ReferenceFormatApiTest` |
| R2 | Each new order (placed or imported), supplier order and suggested product reference is written with the kind's current format. A reference already used in the workspace, or handed out earlier in the same request, is never given twice: `{number}` moves on to the next number, `{random}` is drawn again, a format without either gets `-2`, `-3`… (`PRI-FOR-2`) | `ReferenceFormat::issue()`, `ReferenceGenerator`, `ProductReferenceGenerator` | `ReferenceFormatTest`, `ReferenceUseCasesTest` |
| R3 | The number goes up by one at each reference issued (taken numbers skipped) and never goes back on its own: deleting items does not reuse their numbers. A product suggestion shown in the form but not used does not consume its number | `ReferenceFormat::$nextNumber` | `ReferenceFormatTest`, `ReferenceUseCasesTest` |
| R4 | Saving a format applies it either to **future creations only** (existing references unchanged, numbering continues) or **to existing items as well**: every item of that kind is renumbered **oldest first** (orders by date and time, supplier orders by order day, products by creation) from number 1, in one transaction; the next creation continues after them | `ChangeReferenceFormatHandler`, `ReferenceRenumbering` | `ReferenceUseCasesTest`, `ReferenceFormatApiTest`, `references.spec.js` |
| R5 | Renumbering products replaces references typed by hand too; they are the SKUs written to SumUp and Etsy, so the catalogue export and « Écrire les références sur Etsy » should be redone (the form says so) | `ReferenceFormatForm.vue` | — |
| R6 | The service's own references kept with imported sales (SumUp transaction code, `ETSY-<receipt id>`) never change | `ImportedSale` | — |
| R7 | A product reference typed at creation or edition is kept as typed (P2); the format only drives the suggestion | `CreateProductHandler`, `UpdateProductHandler` | `ProductUseCasesTest` |

## Use cases & API

| Use case | Endpoint |
|---|---|
| `ListReferenceFormats` | `GET /api/references/formats` → `[{kind, template, defaultTemplate, tokens[], example, existing}]` (order, supplier order, product) |
| `PreviewReferenceFormat` | `GET /api/references/formats/{kind}/preview?template=` → `{example}` (next number, today, type `PRI` / name `FOR`), 422 with the reason when invalid |
| `ChangeReferenceFormat` | `PUT /api/references/formats/{kind}` `{template, applyToExisting}` → `{renamed}` |

## UI

**Paramètres › Références**: one row per kind (format and an example), Modifier opens a form: the format field, the
live example (or why the format is refused), one button per tag of the kind (inserted at the cursor), « Revenir au format
d'origine », and — when items exist — « Prochaines créations seulement » / « Renuméroter aussi l'existant » (count of items,
warning about SumUp/Etsy SKUs for products); renumbering asks for a confirmation.
