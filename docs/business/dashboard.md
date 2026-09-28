# Dashboard (Tableau de bord)

Results **per month and per year**, all events together. Home page of the app (`/` → `/tableau-de-bord`).

Model: `src/Domain/Reporting/SalesFigures.php` (the formula, shared with the [event report](event-report.md)),
`MonthlyResults.php` (bucketing); use case `GetDashboard` → `GET /api/dashboard[?year=YYYY]`; UI `DashboardPage.vue`.

## Rules

| # | Rule | Where | Tests |
|---|---|---|---|
| B1 | Same formula as an event: turnover = Σ order totals; URSSAF = 12.8 % × turnover; result = turnover − cost of goods − expenses − URSSAF | `SalesFigures::of()` | `MonthlyResultsTest`, `EventResultTest` |
| B2 | An order counts in the **month of its date** (Europe/Paris) | `MonthlyResults::of()` | `MonthlyResultsTest` (UTC/Paris boundary) |
| B3 | An event's expenses count in the **month the event starts** (even when it spans two months) | `MonthlyResults::of()` | `MonthlyResultsTest` |
| B4 | A year is the **sum of its months** (URSSAF rounded per month, then summed) | `MonthlyResults::year()`, `SalesFigures::add()` | `MonthlyResultsTest` |
| B5 | Selectable years = years with orders or expenses, plus the current year | `GetDashboardHandler` | `GetDashboardTest` |

## Display

- Year selector; tiles: chiffre d'affaires (+ number of orders), coûts (achats + dépenses), URSSAF, résultat.
- Bar chart of the monthly result from a zero baseline (green gain, red loss, hover/focus for figures).
- Table month by month with the year total (accessible view of the chart), and a table of every year.
