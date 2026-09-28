# Events (Événements)

An **event** is a real-world occasion where products are sold: convention, market, fair…

| Field | Meaning |
|---|---|
| `name`, `location` | Required free text (the location is a plain string) |
| `period` | Inclusive range of **whole days** in Europe/Paris (`DateRange`) — a one-day event has start = end |
| `expenses` | Money spent for the event: `label` + strictly positive `amount` |

Model: `src/Domain/Event/Event.php`, `Expense.php`, `EventScheduler.php`, `src/Domain/Shared/DateRange.php`.

## Rules

| # | Rule | Where | Tests |
|---|---|---|---|
| E1 | Name and location are required | `Event::describe()` | `EventTest` |
| E2 | End date ≥ start date | `DateRange::fromDates()` | `DateRangeTest` |
| E3 | A moment belongs to an event when its **Paris local date** is within the period (both ends included) | `DateRange::covers()`, `Event::covers()`, `EventRepository::findCovering()` | `DateRangeTest`, `EventUseCasesTest` |
| E4 | **Events never overlap.** Guarantees an order date maps to at most one event (needed by the SumUp auto-link) | `EventScheduler::ensureFree()` used by `ScheduleEventHandler`/`UpdateEventHandler` | `EventUseCasesTest` |
| E5 | Rescheduling must keep every existing order of the event inside the new period | `UpdateEventHandler` (see [orders](orders.md)) | `OrderUseCasesTest` |
| E6 | Expense label required, amount > 0 — on creation and when revised; expenses can be edited and removed | `Event::addExpense()`, `Event::reviseExpense()`, `Expense::revise()`, `Event::removeExpense()` | `EventTest`, `EventUseCasesTest` |

The profitability of an event is described in [event-report.md](event-report.md).

## Use cases & API

| Use case | Endpoint |
|---|---|
| `ScheduleEvent` | `POST /api/events` `{name, location, startDate, endDate}` (dates `YYYY-MM-DD`) |
| `UpdateEvent` | `PUT /api/events/{id}` (same body) |
| `ListEvents` | `GET /api/events` (most recent first, with expenses total, order count, **turnover and result** computed like the [event report](event-report.md)) |
| `GetEvent` | `GET /api/events/{id}` (with expenses) |
| `AddExpense` | `POST /api/events/{id}/expenses` `{label, amount}` |
| `ReviseExpense` | `PUT /api/events/{id}/expenses/{expenseId}` `{label, amount}` |
| `RemoveExpense` | `DELETE /api/events/{id}/expenses/{expenseId}` |

UI: `/evenements` (list with CA, dépenses and **résultat** of each event + creation modal), `/evenements/{id}` (details, edit, expenses, report).
