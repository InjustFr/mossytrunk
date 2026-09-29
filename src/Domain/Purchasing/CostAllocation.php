<?php

declare(strict_types=1);

namespace App\Domain\Purchasing;

use App\Domain\Shared\Money;

final class CostAllocation
{
    /**
     * @param list<Money> $weights
     *
     * @return list<Money>
     */
    public static function proportionally(Money $amount, array $weights): array
    {
        $total = Money::sum($weights)->amount();
        if (0 === $total) {
            return self::equally($amount, \count($weights));
        }

        $shares = [];
        $remainders = [];
        foreach ($weights as $index => $weight) {
            $exact = $amount->amount() * $weight->amount();
            $shares[$index] = intdiv($exact, $total);
            $remainders[$index] = $exact % $total;
        }
        arsort($remainders);
        $left = $amount->amount() - array_sum($shares);
        foreach (array_keys($remainders) as $index) {
            if ($left <= 0) {
                break;
            }
            ++$shares[$index];
            --$left;
        }

        return array_map(Money::cents(...), $shares);
    }

    /**
     * @return list<Money>
     */
    public static function equally(Money $amount, int $parts): array
    {
        if (0 === $parts) {
            return [];
        }

        $base = intdiv($amount->amount(), $parts);
        $left = $amount->amount() - $base * $parts;

        return array_map(static fn (int $index): Money => Money::cents($base + ($index < $left ? 1 : 0)), range(0, $parts - 1));
    }
}
