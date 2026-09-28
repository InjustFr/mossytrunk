# Discounts (Remises)

Discounts are **bundle discounts**: *N units among a set of products cost a fixed price together*.
Example: a sticker costs 4 €, "3 stickers pour 10 €" → 3 stickers cost 10 € instead of 12 €.

| Field | Meaning |
|---|---|
| `name` | Label shown on orders (e.g. « 3 stickers pour 10 € ») |
| `eligibleProducts` | Explicit list of products; **any variant** of an eligible product counts; eligible products can be mixed in one bundle |
| `bundleSize` | Units per bundle (≥ 2) |
| `bundlePrice` | Price of one bundle (> 0) |
| `active` | Only active rules are applied to new orders |

Model: `src/Domain/Discount/DiscountRule.php`, `DiscountCalculator.php`, `AppliedDiscount.php`, `BasketLine.php`.

## Rules

| # | Rule | Where | Tests |
|---|---|---|---|
| D1 | Name required, ≥ 1 eligible product, bundle size ≥ 2, bundle price > 0 | `DiscountRule::redefine()` | `DiscountRuleTest` |
| D2 | Discounts are **applied automatically** when an order is placed manually; the user never picks them | `DiscountCalculator::calculate()` called by `PlaceOrderHandler` / `PreviewOrderHandler` | `DiscountCalculatorTest`, `OrderUseCasesTest` |
| D3 | A bundle is only applied if it saves money (sum of its units' prices > bundle price) | `DiscountCalculator::bestBundle()` | `DiscountCalculatorTest` |
| D4 | Each unit belongs to at most one bundle | `DiscountCalculator::calculate()` | `DiscountCalculatorTest` |
| D5 | Selection is greedy: repeatedly apply the bundle with the biggest saving, built from the most expensive eligible units (customer-friendly); ties broken by rule name | `DiscountCalculator` | `DiscountCalculatorTest` |
| D6 | Several bundles of the same rule are grouped on the order as one line « name ×n » | `DiscountCalculator::calculate()` | `DiscountCalculatorTest` |
| D7 | Orders store a **snapshot** (label + amount) of applied discounts: editing, deactivating or deleting a rule never changes past orders | `Order::appliedDiscounts` | `OrderTest` |
| D8 | Imported SumUp orders don't get automatic discounts; SumUp's own discount is kept as « Remise SumUp » | see [sumup-import.md](sumup-import.md) | `ImportFromSumUpTest` |

### Worked example
Rules: A = "3 stickers pour 10 €" (sticker 4 €, sticker XL 6 €). Basket: 2 stickers + 2 stickers XL.
Units sorted: 6, 6, 4, 4 → best bundle 6+6+4 = 16 € → 10 € (saving 6 €). One sticker left: no more bundle. Order discount: 6 €.

## Use cases & API

| Use case | Endpoint |
|---|---|
| Create | `POST /api/discount-rules` `{name, productIds[], bundleSize, bundlePrice}` |
| Update | `PUT /api/discount-rules/{id}` |
| Activate / deactivate | `PUT` / `DELETE /api/discount-rules/{id}/activation` |
| Delete | `DELETE /api/discount-rules/{id}` |
| List | `GET /api/discount-rules` |

UI: `/remises` — list with active switch, edit and two-step delete; form with product picker and "normal price of N units" hint.
