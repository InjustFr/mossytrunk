<?php

declare(strict_types=1);

namespace App\Domain\Discount;

use App\Domain\Shared\Money;

/**
 * Computes which bundle discounts apply to a basket.
 *
 * Algorithm (greedy, deterministic):
 *  1. Expand the basket into units, each with its unit price.
 *  2. For every active rule, the best bundle still available is made of its `bundleSize` most
 *     expensive eligible units; its saving = sum of those unit prices − bundle price.
 *  3. Apply the bundle with the biggest positive saving (ties: rule name), remove its units, repeat.
 *  4. Stop when no rule can form a bundle with a positive saving.
 * A unit is used by at most one bundle. Savings are grouped per rule in the result.
 */
final class DiscountCalculator
{
    /**
     * @param list<BasketLine>   $basket
     * @param list<DiscountRule> $rules
     *
     * @return list<AppliedDiscount>
     */
    public function calculate(array $basket, array $rules): array
    {
        $rules = array_values(array_filter($rules, static fn (DiscountRule $rule): bool => $rule->isActive()));
        usort($rules, static fn (DiscountRule $a, DiscountRule $b): int => strcmp($a->name(), $b->name()));

        $units = $this->expand($basket);
        /** @var array<string, array{rule: DiscountRule, bundles: int, saving: Money}> $applied */
        $applied = [];

        while (null !== $best = $this->bestBundle($units, $rules)) {
            [$rule, $unitKeys, $saving] = $best;
            foreach ($unitKeys as $key) {
                unset($units[$key]);
            }

            $id = (string) $rule->id();
            $applied[$id] ??= ['rule' => $rule, 'bundles' => 0, 'saving' => Money::zero()];
            ++$applied[$id]['bundles'];
            $applied[$id]['saving'] = $applied[$id]['saving']->add($saving);
        }

        return array_values(array_map(
            static fn (array $entry): AppliedDiscount => new AppliedDiscount(
                $entry['bundles'] > 1 ? \sprintf('%s ×%d', $entry['rule']->name(), $entry['bundles']) : $entry['rule']->name(),
                $entry['saving'],
            ),
            $applied,
        ));
    }

    /**
     * @param list<BasketLine> $basket
     *
     * @return array<int, array{productId: \Symfony\Component\Uid\Ulid, typeId: ?\Symfony\Component\Uid\Ulid, price: Money}> sorted by price, most expensive first
     */
    private function expand(array $basket): array
    {
        $units = [];
        foreach ($basket as $line) {
            for ($i = 0; $i < $line->quantity; ++$i) {
                $units[] = ['productId' => $line->productId, 'typeId' => $line->typeId, 'price' => $line->unitPrice];
            }
        }

        usort($units, static fn (array $a, array $b): int => $b['price']->amount() <=> $a['price']->amount());

        return $units;
    }

    /**
     * @param array<int, array{productId: \Symfony\Component\Uid\Ulid, typeId: ?\Symfony\Component\Uid\Ulid, price: Money}> $units
     * @param list<DiscountRule>                                                      $rules
     *
     * @return array{DiscountRule, list<int>, Money}|null
     */
    private function bestBundle(array $units, array $rules): ?array
    {
        $best = null;

        foreach ($rules as $rule) {
            $eligible = array_filter($units, static fn (array $unit): bool => $rule->isEligible($unit['productId'], $unit['typeId']));
            if (\count($eligible) < $rule->bundleSize()) {
                continue;
            }

            $bundle = \array_slice($eligible, 0, $rule->bundleSize(), preserve_keys: true);
            $regular = Money::sum(array_column($bundle, 'price'));
            $saving = $regular->subtract($rule->bundlePrice());

            if ($saving->isPositive() && (null === $best || $saving->greaterThan($best[2]))) {
                $best = [$rule, array_keys($bundle), $saving];
            }
        }

        return $best;
    }
}
