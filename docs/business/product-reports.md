# Product report (Bilan produits)

`/reports/products` (Bureau › « Bilan produits ») tells how the catalogue sold over a period, without touching the product pages.

| # | Rule | Where | Tests |
|---|---|---|---|
| PR1 | The period is « 12 derniers mois » (from the first day of the month eleven months ago to today, the default), a calendar year, or « Depuis le début » (from the first sale); days in Europe/Paris. Refunded orders are left out | `ReportPeriod` | `ProductReportsTest` |
| PR2 | **Palmarès**: every article sold in the period with its units, its sales at full price, its revenue after its share of the orders' discounts (O20), the discounts given (full price − revenue), its margin (revenue − buying cost, flagged when a cost is unknown) and its stock today; ranked by revenue, units, discounts or margin. Supplies are not listed | `GetProductsReportHandler` | `ProductReportsTest`, `product-reports.spec.js` |
| PR3 | **Product story**, month by month over the period: units sold, full-price sales and revenue; stock arrivals (every lot: purchases, supplier orders, inventory surpluses, returns) and departures (sales, inventory losses, use as supply); the stock at each month's end, worked back from today's stock | `GetProductReportHandler`, `ProductMovements` | `ProductReportsTest` |
| PR4 | **Discounts of a product**: every discount rule with a condition on the product or its type (any variant), cut to the period; « Remisé N jours sur M » counts the days covered by at least one of them, and each month shows the share of the full price given away | `DiscountRule::concerns()`, `ValidityPeriod::within()` | `ProductReportsTest` |

## API

| Use case | Endpoint |
|---|---|
| `GetProductsReport` | `GET /api/reports/products?period=12m\|YYYY\|all` → `{period, years, products, totals}` |
| `GetProductReport` | `GET /api/reports/products/{id}?period=…` → `{product, period, months, discounts, discountedDays}` |

## UI

- A one-sentence summary of the period (cashed, items sold, discounts given), then the **palmarès**: one bar per product — in « Encaissé », its length is the full-price sales, solid for what was cashed and hatched for what discounts gave away; the other rankings draw the chosen figure. Hover for the details.
- Clicking a product opens its story beside the list (above it on narrow screens): sales per month, stock movements (arrivals above the line, departures below, the month-end stock as a line), and the discount rules as spans over the period with each month's discounted share. Period and product live in the URL (`?period=`, `?product=`).
