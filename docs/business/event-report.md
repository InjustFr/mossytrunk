# Event report (Bilan d'un événement)

At the end of an event the user wants to know what was sold, what was spent and what is left.
The report is **computed on the fly** from the event and its orders — nothing is stored.

Model: `src/Domain/Reporting/EventResult.php`, `SalesFigures.php` (formula shared with the [dashboard](dashboard.md)), `UrssafContribution.php`, `ProductSales.php`;
use case `GetEventReport` → `GET /api/events/{id}/report`; UI: report card on `/evenements/{id}` (`EventReport.vue`).

## Formula

```
gross sales        = Σ order subtotals (unit selling price snapshot × qty)
discounts          = Σ order discounts (rules, « Remise SumUp »)
turnover (CA)      = gross sales − discounts                  = Σ order totals
cost of goods      = Σ line costs (units taken from stock)
URSSAF             = 12.8 % × turnover   (rounded to the cent, half up)
result (Résultat)  = turnover − cost of goods − expenses − URSSAF
```

| # | Rule | Where | Tests |
|---|---|---|---|
| R1 | URSSAF contributions are **12.8 % of the turnover** (after discounts), micro-entrepreneur flat rate for selling goods. The rate lives in one constant | `UrssafContribution::RATE_BASIS_POINTS` | `EventResultTest`, `MoneyTest` |
| R2 | Cost of goods uses the cost of the units **taken from stock at the time of sale** (oldest lot first, see [stock.md](stock.md)). A cost of 0 means *unknown* (product never bought) and is flagged with a warning icon | `EventResult::productSales()` | `EventResultTest` |
| R3 | Only the event's own orders count | `GetEventReportHandler` | `EventReportTest` |
| R4 | The result can be negative (loss) | `EventResult` | `EventResultTest` |

## Display (visual grouping only — not domain concepts)

The report is a **receipt**: the result in large type (with its share of the turnover and the number of orders), next to the ledger
chiffre d'affaires − coût d'achat − dépenses − URSSAF (rate shown) = **Résultat**.

- **Chiffre d'affaires** unfolds (open when there are orders) into gross sales, discounts and the **orders recap**: an accordion
  grouped by **type → product → variants** (quantity and sales before discounts at each level; warning icon when a cost is unknown).
  Types and names are the products' current ones (untyped or deleted products under « Sans type »), see `GetEventReport/OrderRecap.php` (`OrderRecapTest`).
  The article list can be hidden with « Afficher le détail des articles » (remembered in the browser). Link to the orders list filtered on the event.
- Expenses are listed (and edited) in their own card below the receipt.
- An **upcoming** event without orders shows its **committed expenses** instead of a (negative) result.
