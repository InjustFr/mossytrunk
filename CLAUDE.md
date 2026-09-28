# MossyTrunk — project guide for Claude

Small-business management app. Module 1 = **Order Management** (products, events, orders, bundle discounts, SumUp import, event profitability).
UI language: **French**. Code, comments, commits: **English**.

**Business rules live in [`docs/business/`](docs/business/README.md)** — read the relevant page before touching a domain concept, and update it in the same commit when a rule changes.

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
make e2e             # builds assets, boots php-e2e (APP_ENV=test, fake SumUp), runs Playwright
docker compose exec php php bin/console …
```
Host port overridable with `HTTP_PORT`. Postgres exposed on `5433` (compose.override.yaml).

## Backend architecture (Onion) — `src/`

| Layer | Contains | May depend on |
|---|---|---|
| `Domain/` | Entities (rich, **no setters**), value objects, domain services, repository **interfaces**, domain exceptions | nothing |
| `Application/` | Use cases: `<Context>/<UseCase>/{Command\|Query, Handler}` + read models; ports (e.g. `SumUpGateway`) | Domain |
| `Infrastructure/` | Doctrine repositories, SumUp HTTP adapter + fake | Domain, Application |
| `Presentation/` | `Api/` JSON controllers + request DTOs, `Web/` Twig shells | Application, Domain |

Enforced by `deptrac.yaml`. Rules:
- Doctrine mapping = **attributes on Domain entities** (accepted trade-off: no separate persistence models).
- Entities change state only through intention-revealing methods (`reprice()`, `addExpense()`…). Named constructors (`Product::create()`), private constructor if useful.
- Invariants throw subclasses of `App\Domain\Shared\DomainException` → returned as HTTP 422 `{detail}` by `Presentation\Api\DomainExceptionListener`.
- Money = `App\Domain\Shared\Money` (integer cents). The API exchanges **cents** as integers.
- Handlers are plain invokable services (`__invoke(Command)`), called directly by controllers. They persist through repository interfaces and end with `App\Application\Transaction::commit()` (Doctrine flush).

## Frontend — `assets/vue/`

- Navigation goes through **Turbo Drive** (`symfony/ux-turbo`): no full reload, mossy green top progress bar (`.turbo-progress-bar`). The UX Vue Stimulus controller unmounts/mounts page apps on each visit, so read URL state in `setup()` (not at module level) and navigate from code with `visit()` (`composables/useNavigation.js`), never `window.location`.
- `pages/` — one per route, mounted from `templates/page.html.twig` via `PageController`. **Thin orchestrators**: layout + components + composables, no business logic.
- `layouts/AppLayout.vue` — vertical sidebar nav, page title + header actions, toast host.
- `components/<context>/` — feature components; `components/ui/` — generic building blocks.
- `composables/` — data fetching/state (`useOrders`, …), `useApi`, `useToast`, `useMoney`.
- CSS: **BEM** class names (`block__element--modifier`), `<style scoped>`, design tokens in `assets/styles/tokens.css` (light mode, mossy green accent `--color-accent`). Small transitions only (`--transition`). **Units: `rem` (1rem = 16px) or `vw`/`vh` only — never `px` or `em`**, including media queries and Lucide `size` (`size="1rem"`).
- Creation/edition forms open in `components/ui/BaseModal.vue` from a header button (`variant="drawer"` for order entry so the list stays visible). Forms emit `saved`/`cancel`; the page closes the modal and reloads.
- Look & feel: calm catalogue style (inspired by tikamoon.com) — white surfaces on light warm grey, near-black text, thin borders, 0.5rem radius (`--radius`), serif display font (DM Serif Display) for brand/titles, Inter for text, small uppercase letter-spaced labels. Fonts are self-hosted via `@fontsource`. Icons: **Lucide** (`@lucide/vue`, e.g. `import { X } from '@lucide/vue'`), `aria-hidden` when decorative, `role="img"` + `aria-label` when meaningful; no emoji/glyph icons.
- Tables use `components/ui/DataTable.vue`; pass `:items` to paginate client-side (default slot gets `{ rows }` = current page, pagination bar with 20/50/100 per page appears above 20 rows). Playwright tests look rows up after searching/filtering, never assuming they are on page 1.
- Confirm user actions with `useToast().success/error`.
- Add `data-test` attributes only when a role/label selector is not practical for Playwright.

## Mock data

`fixtures/` (namespace `App\Fixtures`, dev/test only, outside the onion layers): Foundry factories build entities through their
named constructors (`Instantiator::namedConstructor()`, hydration disabled — no setters). `ConventionSeasonStory` creates a
catalogue with variants, bundle rules, past events with expenses and orders (discounts computed by `DiscountCalculator`) and an upcoming event.

## Testing expectations

- Unit test (`tests/Unit`) every business rule (entities, value objects, domain services).
- Functional test (`tests/Functional`) for every use-case handler (real DB, KernelTestCase) and API endpoints (WebTestCase).
- Playwright (`e2e/tests`) for user journeys. In `test` env `SumUpGateway` is bound to `FakeSumUpGateway`.

## Git

One commit per feature, conventional messages. No co-author lines.
