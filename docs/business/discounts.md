# Discounts (Remises)

Discounts are **bundle discounts**: *N units among a set of products cost a fixed price together*.
Example: a sticker costs 4 €, "3 stickers pour 10 €" → 3 stickers cost 10 € instead of 12 €.

| Field | Meaning |
|---|---|
| `name` | Label shown on orders (e.g. « 3 stickers pour 10 € ») |
| `eligibleTypes` | Product types: **every product of these types** is eligible, including products created later |
| `eligibleProducts` | Extra explicit products; **any variant** of an eligible product counts |

Eligible units (from any listed type or product) **can be mixed** in one bundle.
| `bundleSize` | Units per bundle (≥ 2) |
| `bundlePrice` | Price of one bundle (> 0) |
| `active` | Only active rules are applied to new orders |

Model: `src/Domain/Discount/DiscountRule.php`, `DiscountCalculator.php`, `AppliedDiscount.php`, `BasketLine.php`.

## Rules

| # | Rule | Where | Tests |
|---|---|---|---|
| D1 | Name required, ≥ 1 eligible type or product, bundle size ≥ 2, bundle price > 0 | `DiscountRule::redefine()` | `DiscountRuleTest` |
| D1b | A unit is eligible when its product is listed **or** its product's type is listed | `DiscountRule::isEligible(productId, typeId)`; the type travels in `SellableItem`/`BasketLine` | `DiscountRuleTest`, `DiscountCalculatorTest` |
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

### Mixed basket: 2 prints + 1 sticker
Print 15 €, sticker 4 €. Basket: 2 prints + 1 sticker (34 € without discount).
- Rules « 2 prints pour 25 € » (type Print) and « 3 stickers pour 10 € » (type Sticker): the print bundle applies, the lone sticker stays at full price → **29 €**.
- One rule « 3 articles pour 30 € » on types Print **and** Sticker: the three units form one bundle → **30 €**.
- With both kinds of rules active, the calculator applies the bundle saving the most first (D5).
Tests: `DiscountCalculatorTest::testTwoPrintsAndOneSticker…`.

## Use cases & API

| Use case | Endpoint |
|---|---|
| Create | `POST /api/discount-rules` `{name, typeIds[], productIds[], bundleSize, bundlePrice}` |
| Update | `PUT /api/discount-rules/{id}` |
| Activate / deactivate | `PUT` / `DELETE /api/discount-rules/{id}/activation` |
| Delete | `DELETE /api/discount-rules/{id}` |
| List | `GET /api/discount-rules` |

UI: `/remises` — list with active switch, edit and two-step delete; each rule shows the normal price of its N units at today's selling prices (a range when eligible products differ) and the customer's saving; form with type chips, product picker (products covered by a chosen type shown ticked) and "normal price of N units" hint.
