# MossyTrunk — project guide for Claude

Small-business management app. Module 1 = **Order Management** (products, events, orders, discount rules, imports from connected services, event profitability), then **Stock** (FIFO lots, inventory after events), **Supplier orders** (ordered → received into stock), **Designs** (declined onto gabarits, validated into products), **Accounting** (URSSAF declarations, CSV export) and connected services (SumUp, Etsy: added per workspace in Paramètres, see `docs/business/imports.md`).
UI language: **French**. Code, comments, commits: **English**.

**Business rules live in [`docs/business/`](docs/business/README.md)** — read the relevant page before touching a domain concept, and update it in the same commit when a rule changes.

## Code style

- **Never write comments** — no docblocks, no inline `//`/`#`, no `<!-- -->`, no `{# #}`. Clear code prevails: express intent through names, small methods and types. Only type-only PHPDoc the type system needs (generics/array shapes like `@return list<Product>`, `@implements`) is allowed.

Generic Symfony conventions: see `AGENTS.md` (this file wins when they disagree — e.g. we run everything through Docker, not `symfony serve`).

## Stack

- Symfony 8.1, PHP 8.5 (FrankenPHP), Doctrine ORM 3, PostgreSQL 18, ULIDs (`symfony/uid`, Doctrine `ulid` type).
- Vue 3 mounted by Symfony UX Vue (`vue_component()`), compiled by Webpack Encore.
- Tests: PHPUnit 13 (unit + functional, DAMA rollback per test), Playwright (e2e).

## Running things (always in Docker)

```bash
make up              # php + database + node (encore watch) → http://localhost:8080
make db              # create + migrate dev DB
make fixtures        # reset dev DB with mock data (Foundry story fixtures/Story/ConventionSeasonStory.php)
make migration       # doctrine:migrations:diff after mapping changes
make test            # PHPUnit (migrates test DB first); make test-unit / test-functional
make deptrac         # onion layer rules
make cs / make cs-fix # PHP-CS-Fixer (@Symfony + declare(strict_types=1) in every file)
make phpstan         # PHPStan level 10 (phpstan.dist.neon, Symfony/Doctrine/PHPUnit extensions) — keep it at 0 errors
make e2e             # builds assets, boots php-e2e (APP_ENV=test, fake SumUp/Etsy gateways), runs Playwright
docker compose exec php php bin/console …
make deploy DEPLOY_HOST=user@server DEPLOY_DIR=…   # run the current commit's image (published by CI) on the server; make push = manual push
```
Host port overridable with `HTTP_PORT`. Postgres exposed on `5433` (compose.override.yaml).

Production: `Dockerfile` stages `base` → `vendor`/`assets` → `prod` (the last stage `dev` is what `compose.yaml` builds). `docker/php/docker-entrypoint.sh` waits for the DB, migrates, warms the cache. GitHub Actions (`.github/workflows/image.yml`) pushes the `prod` image to Docker Hub (`docker.io/injust/mossytrunk:<short sha>` + `:latest`, public) on every push to `main`, using the `DOCKERHUB_USERNAME`/`DOCKERHUB_TOKEN` repository secrets. Server files live in `deploy/` (see `deploy/README.md`); the port is `APP_PORT`, reverse-proxy trust via `SYMFONY_TRUSTED_PROXIES`/`SYMFONY_TRUSTED_HEADERS` env. A new env var must be added to `deploy/.env.dist`.

## Backend architecture (Onion) — `src/`

| Layer | Contains | May depend on |
|---|---|---|
| `Domain/` | Entities (rich, **no setters**), value objects, domain services, repository **interfaces**, domain exceptions | nothing |
| `Application/` | Use cases: `<Context>/<UseCase>/{Command\|Query, Handler}` + read models; ports (e.g. `Integration\SalesConnector`) | Domain |
| `Infrastructure/` | Doctrine repositories, service connectors (`Connector/<Service>/`: HTTP adapters, payload mappers, fakes) | Domain, Application |
| `Presentation/` | `Api/` JSON controllers + request DTOs, `Web/` Twig shells | Application, Domain |

Enforced by `deptrac.yaml`. Rules:
- Doctrine mapping = **attributes on Domain entities** (accepted trade-off: no separate persistence models).
- Entities change state only through intention-revealing methods (`reprice()`, `addExpense()`…). Named constructors (`Product::create()`), private constructor if useful.
- Invariants throw subclasses of `App\Domain\Shared\DomainException` → returned as HTTP 422 `{detail}` by `Presentation\Api\DomainExceptionListener`.
- Money = `App\Domain\Shared\Money` (integer cents). The API exchanges **cents** as integers.
- Handlers are plain invokable services (`__invoke(Command)`), called directly by controllers. They persist through repository interfaces and end with `App\Application\Transaction::commit()` (Doctrine flush).

## Accounts & security

- Users (`Domain\Identity\User`) belong to one `Workspace`; no registration: `docker compose exec php php bin/console app:user:create <email> --workspace=<name>` emails an invitation link. Emails land in Mailpit: http://localhost:8025.
- Symfony Security stays out of the Domain: `Infrastructure\Security\SecurityUser` wraps the domain user, `UserProvider` loads it. Session firewall with `form_login` on `/connexion` (sessions stored in PostgreSQL table `sessions` by `PdoSessionHandler` on the Doctrine connection, so deploys keep users signed in), CSRF-protected logout on `/deconnexion`, password pages under `/mot-de-passe`.
- Auth pages are plain HTML POST forms (`data-turbo="false"`) rendered by Vue pages with `AuthLayout`; the signed-in user is exposed to `AppLayout` through `#app-session` (`composables/useSession.js`).
- The JSON API uses the session cookie: `/api` answers 401 when signed out (`useApi` then goes to `/connexion`) and `SameOriginGuard` rejects cross-site writes.
- **Workspace scoping**: every aggregate root (Product, ProductType, Event, DiscountRule) is created with a `Workspace` (`Order` takes its event's). Every Doctrine repository query filters on `WorkspaceContext::current()` (Application port, `SecurityWorkspaceContext` reads the signed-in user) — a new repository method must too. Unique constraints are per workspace.
- **Secrets**: workspace secrets go through `Application\Workspace\WorkspaceSecrets` (`keep`/`reveal`/`forget`, named `SecretName::of($service, $field)` → `sumup_api_key`, `etsy_refresh_token`…) and are encrypted by `SodiumSecretCipher` with `APP_ENCRYPTION_KEY` (dev/test keys in `.env.dev`/`.env.test`, prod via the secrets vault; `app:encryption:generate-key`). Never return a plain key from the API.
- **Connected services**: service-specific code lives only in `Infrastructure/Connector/<Service>/` — a `SalesConnector` (or `AuthorizingConnector` for OAuth) tagged `app.sales_connector` that describes its fields/defaults and maps its payloads to `ExternalSale`s. Application stays generic (`Application/Integration/`: `Connectors` registry, `ConnectionSession` for credentials/token refresh, `ImportSalesHandler`, `ExternalItemResolver`); a workspace adds a service as a `Domain\Integration\ServiceConnection` (one per service). Adding a service = a connector + a fake gateway bound in `when@test`; see `docs/business/imports.md`.
- Fixtures log in as `demo@mossytrunk.local` / `mossytrunk` (workspace « Atelier Mousse ») or `autre@mossytrunk.local` / `mossytrunk` (« Autre atelier »).
- Tests: `tests/Support/ActsAsUser` (`signedInClient()` for WebTestCase, `actAsMemberOf()` in `setUp()` for KernelTestCase); unit tests build entities with `TestWorkspace::get()`. E2E: `make e2e` creates `e2e@mossytrunk.local`, `auth.setup.js` sets its password from the Mailpit invitation and saves the session for all specs.

## Frontend — `assets/vue/`

- Navigation goes through **Turbo Drive** (`symfony/ux-turbo`): no full reload, mossy green top loading bar from `assets/progress-bar.js` (Turbo's own bar is disabled): it spans the Turbo visit **and** API calls (`useApi` calls `begin()`/`end()`), shown at least 300ms. The UX Vue Stimulus controller unmounts/mounts page apps on each visit, so read URL state in `setup()` (not at module level) and navigate from code with `visit()` (`composables/useNavigation.js`), never `window.location`.
- `pages/` — one per route, mounted from `templates/page.html.twig` via `PageController`. **Thin orchestrators**: layout + components + composables, no business logic.
- `layouts/AppLayout.vue` — vertical sidebar nav, page title + header actions, toast host.
- `components/<context>/` — feature components; `components/ui/` — generic building blocks.
- Interactive widgets are built on **Reka UI** (`reka-ui`, headless). `components/ui/Base*` wrappers are **thin**: a Reka primitive + BEM/tokens theming, props and `v-model` fall through to the Reka root; use Reka features (`defaultValue`, `formatOptions`, `ItemIndicator`, providers) instead of re-implementing behaviour. Conversions only where the API contract needs them (`BaseMoneyField` v-model in cents, `BaseDatePicker`/`BaseDateRangePicker` in ISO strings). Map: Dialog (`BaseModal`), AlertDialog (`ConfirmButton`), Toast, Select (`BaseSelect`, `options: [{ value, label }]`), Combobox (`BaseCombobox`), Checkbox, Switch, NumberField (`BaseNumberField`, `BaseMoneyField`), DatePicker/DateRangePicker, Pagination, Tooltip (`IconButton`, provider in `AppLayout`), ScrollArea (`DataTable`), Label (`FormField`), VisuallyHidden, NavigationMenu (sidebar), ConfigProvider `fr-FR` (layouts); feature components use ToggleGroup, TagsInput, Listbox, Collapsible, RadioGroup (`settings/ServiceOptions`). No native `<select>`/checkbox/date/number inputs. Portaled parts (dialog, select/combobox content, tooltip, toast, calendar) are styled in a non-scoped `<style>` block since scoped attributes do not reach teleported content. Playwright helpers: `choose()` (`e2e/tests/support/select.js`), `fillDate()`/`fillDateRange()` (`support/date.js`, types dd/mm/yyyy[/hh/mm] segments).
- `composables/` — data fetching/state (`useOrders`, …), `useApi`, `useToast`, `useMoney`.
- CSS: **BEM** class names (`block__element--modifier`), `<style scoped>`, design tokens in `assets/styles/tokens.css` (light mode, mossy green accent `--color-accent`). Small transitions only (`--transition`). **Units: `rem` (1rem = 16px) or `vw`/`vh` only — never `px` or `em`**, including media queries and Lucide `size` (`size="1rem"`).
- Creation/edition forms open in `components/ui/BaseModal.vue` from a header button (`variant="drawer"` for order entry so the list stays visible). Forms emit `saved`/`cancel`; the page closes the modal and reloads. Form action rows are right-aligned (`justify-content: flex-end`) with the confirming/submit button last (rightmost); table row actions sit in a right-aligned `data-table__cell--actions` cell.
- Look & feel: calm catalogue style (inspired by tikamoon.com) — white surfaces on light warm grey, near-black text, thin borders, 0.5rem radius (`--radius`), display font Patua One (Google font, self-hosted) for brand/titles, Inter for text, small uppercase letter-spaced labels. Fonts are self-hosted via `@fontsource`. Icons: **Lucide** (`@lucide/vue`, e.g. `import { X } from '@lucide/vue'`), `aria-hidden` when decorative, `role="img"` + `aria-label` when meaningful; no emoji/glyph icons. Row actions in tables/lists are icon-only (`components/ui/IconButton.vue`: Pencil = modifier, Trash2 = supprimer via `ConfirmButton :icon`), with the action as `label` (aria-label + tooltip).
- Tables use `components/ui/DataTable.vue`; pass `:items` to paginate client-side (default slot gets `{ rows }` = current page, pagination bar with 20/50/100 per page appears above 20 rows). Playwright tests look rows up after searching/filtering, never assuming they are on page 1.
- Status pills use `components/ui/StatusBadge.vue` (`tone`: neutral, warning, danger, success). `BaseButton` renders a link when given `href`.
- Confirm user actions with `useToast().success/error`.
- Add `data-test` attributes only when a role/label selector is not practical for Playwright.

## Mock data

`fixtures/` (namespace `App\Fixtures`, dev/test only, outside the onion layers): Foundry factories build entities through their
named constructors (`Instantiator::namedConstructor()`, hydration disabled — no setters). `ConventionSeasonStory` creates a
catalogue with variants, discount rules, past events with expenses and orders (discounts computed by `DiscountCalculator`) and an upcoming event in workspace « Atelier Mousse » (user `demo@mossytrunk.local` / `mossytrunk`);
`OtherWorkspaceStory` adds « Autre atelier » (user `autre@mossytrunk.local` / `mossytrunk`) with a few products, to check isolation.

## Testing expectations

- PHPStan runs at **level 10** on `src`, `tests` and `fixtures`: no `mixed` leaks — narrow with `instanceof`/`is_*`, type arrays with shapes (`array{…}`, `list<…>`), read decoded JSON in tests through `tests/Support/Json`. Ignores live in `phpstan.dist.neon` only (never inline).

- Unit test (`tests/Unit`) every business rule (entities, value objects, domain services).
- Functional test (`tests/Functional`) for every use-case handler (real DB, KernelTestCase) and API endpoints (WebTestCase).
- Playwright (`e2e/tests`) for user journeys. In `test` env the connectors' gateways are fakes: `Connector\SumUp\SumUpGateway` → `FakeSumUpGateway`, `Connector\Etsy\EtsyGateway` → `FakeEtsyGateway` (fixture `tests/Fixtures/etsy/receipts.json`, fake OAuth that redirects straight to the callback).

## Git

One commit per feature, conventional messages. No co-author lines.
