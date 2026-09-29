# Discounts

A discount rule is a set of **conditions** (all must be met by the basket) and one **action** applied to the items those conditions pick.
Example: « 2 prints et 1 sticker pour 15 € » → conditions `2 × type Print` and `1 × type Sticker`, action `fixed price 15 €`.

| Field | Meaning |
|---|---|
| `name` | Label shown on orders (e.g. « 2 prints et 1 sticker pour 15 € ») |
| `conditions` | One or more `quantity × target`. A target is a **product type** (every product of that type, including products created later) or a **product** (any of its variants) |
| `action` | `fixedPrice` — the picked items cost this price together · `amountOff` — this amount is taken off the picked items · `percentOff` — this percentage (basis points, 1000 = 10 %) is taken off the picked items |
| `startsOn` / `endsOn` | Optional validity period, whole days (Europe/Paris), both bounds included. Empty = no limit on that side |
| status | Derived from the dates only: « En cours » (today within the period, or no dates), « À venir », « Expirée » |

Model: `src/Domain/Discount/DiscountRule.php`, `DiscountCondition.php` (`ProductCondition`, `TypeCondition`), `DiscountAction.php`, `ValidityPeriod.php`, `DiscountCalculator.php`, `AppliedDiscount.php`, `BasketLine.php`.

## Rules

| # | Rule | Where | Tests |
|---|---|---|---|
| D1 | Name required, ≥ 1 condition, each condition on ≥ 1 unit, a target appears in one condition only; action value > 0 and a percentage ≤ 100 %; the period's end is not before its start | `DiscountRule::redefine()`, `DiscountCondition`, `DiscountAction`, `ValidityPeriod::between()` | `DiscountRuleTest`, `DiscountActionTest` |
| D1b | A unit matches a product condition when it is that product (whatever the variant), a type condition when its product has that type | `ProductCondition::matches()`, `TypeCondition::matches()`; the type travels in `SellableItem`/`BasketLine` | `DiscountCalculatorTest` |
| D2 | Discounts are **applied automatically** when an order is placed manually; the user never picks them. A rule applies when the order's date (not today's) is within its period | `DiscountCalculator::calculate()` called by `OrderPricing` (`PlaceOrderHandler`, `PreviewOrderHandler`, `ImportFromSumUpHandler`), `DiscountRule::appliesOn()` | `DiscountCalculatorTest`, `DiscountRuleUseCasesTest` |
| D3 | Each condition picks its `quantity` **most expensive** still-free matching units; **product conditions pick first**, then type conditions (so « 1 Holo + 1 sticker » keeps the other sticker for the type condition). A missing unit → the rule does not apply | `DiscountCalculator::take()`, `DiscountRule::conditionsMostSpecificFirst()` | `DiscountCalculatorTest` |
| D4 | Saving of an application: fixed price → normal price of the picked units − price; amount off → the amount, at most the normal price; percent off → normal price × percentage, rounded to the cent. Applied only if the saving is positive | `DiscountAction::saving()`, `DiscountCalculator` | `DiscountActionTest`, `DiscountCalculatorTest` |
| D5 | **Several discounts can apply to one order.** Selection is greedy: the application with the **biggest saving** is applied first, its units are removed, and the search starts again on the remaining units — possibly with the same rule again, or another one. Ties broken by rule name | `DiscountCalculator::calculate()` | `DiscountCalculatorTest` |
| D6 | Each unit is used by at most one application. Several applications of the same rule are grouped on the order as one line « name ×n » | `DiscountCalculator::calculate()` | `DiscountCalculatorTest` |
| D7 | Orders store a **snapshot** (label + amount) of applied discounts, plus the rule's id to link to it: editing, stopping or deleting a rule never changes past orders | `Order::appliedDiscounts`, `AppliedDiscount::ruleId` | `OrderTest` |
| D8 | Imported SumUp orders get the rules valid on their date only when they explain SumUp's discount (to within 2 cents), otherwise « Remise SumUp » (see [sumup-import.md](sumup-import.md), S8) | `Order::importFromSumUp()` | `OrderTest`, `ImportFromSumUpTest` |
| D9 | There is no on/off flag: the switch rewrites the dates. Stopping a running rule ends it **yesterday** (refused when it started today: edit or delete it instead); starting an upcoming rule moves its start to **today**. An expired rule has no switch and cannot be started: edit its dates to reuse it. Rules switched off before this rule existed were given an end date of the day before the change | `DiscountRule::startOn()`, `stopBefore()`, `ValidityPeriod::statusOn()` | `DiscountRuleTest`, `DiscountRuleUseCasesTest` |

### Worked example: 2 prints + 1 sticker

Print 15 €, sticker 4 €. Basket: 2 prints + 1 sticker (34 € without discount).
- « 2 prints et 1 sticker pour 15 € » (2 × Print, 1 × Sticker, fixed price 15 €) → saving 19 € → **15 €**.
- Basket of 5 prints + 2 stickers with the same rule → applied twice (« ×2 », saving 38 €), the fifth print stays at full price.
- With also « 3 stickers pour 10 € » active and a basket of 2 prints + 4 stickers: the prints rule saves more, so it takes 2 prints and the most expensive sticker; the 3 other stickers form a « 3 stickers pour 10 € » → both discounts on the order.

## Use cases & API

Create `POST /api/discount-rules` `{name, conditions: [{kind: product|type, id, quantity}], action: {kind: fixedPrice|amountOff|percentOff, value}, startsOn?, endsOn?}` (value in cents, or basis points for a percentage; dates `YYYY-MM-DD` or null) · Update `PUT /api/discount-rules/{id}` · Start today / stop (ends yesterday) `PUT` / `DELETE /api/discount-rules/{id}/activation` · Delete `DELETE /api/discount-rules/{id}` · List `GET /api/discount-rules` (conditions come back with the target's `name`).

UI: `/remises` — list with conditions and action (« 2 × Print + 1 × Sticker pour 15,00 € »), period (« Expirée » badge), normal price at today's selling prices (a range when a type's products differ) and the customer's saving, a switch labelled « En cours » / « À venir » (none once expired), edit and two-step delete. Form: conditions (quantity, Type / Produit, combobox), action (Prix fixe / Remise en € / Remise en %), normal and customer price hint, optional « Valable du » / « Au » dates. `/remises?remise=<id>` opens that rule; a rule discount on an order's detail page links there.
