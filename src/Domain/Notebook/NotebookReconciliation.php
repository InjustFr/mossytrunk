<?php

declare(strict_types=1);

namespace App\Domain\Notebook;

final class NotebookReconciliation
{
    public const float PAIRING_THRESHOLD = 0.5;

    /**
     * @param list<NotebookEntry> $entries
     * @param list<RecordedOrder> $orders
     */
    public static function between(array $entries, array $orders): Reconciliation
    {
        usort($orders, static fn (RecordedOrder $a, RecordedOrder $b): int => [$a->placedAt, $a->reference] <=> [$b->placedAt, $b->reference]);

        $pairings = [];
        $pairedOrders = [];
        foreach (self::candidates($entries, $orders) as $candidate) {
            if (isset($pairings[$candidate->entryIndex]) || isset($pairedOrders[$candidate->orderIndex])) {
                continue;
            }
            $pairings[$candidate->entryIndex] = new Pairing($candidate->entryIndex, $entries[$candidate->entryIndex], $orders[$candidate->orderIndex], $candidate->comparison);
            $pairedOrders[$candidate->orderIndex] = true;
        }
        ksort($pairings);

        return new Reconciliation(
            array_values($pairings),
            array_diff_key($entries, $pairings),
            array_values(array_diff_key($orders, $pairedOrders)),
        );
    }

    /**
     * @param list<NotebookEntry> $entries
     * @param list<RecordedOrder> $orders
     *
     * @return list<PairingCandidate>
     */
    private static function candidates(array $entries, array $orders): array
    {
        $candidates = [];
        foreach ($entries as $entryIndex => $entry) {
            foreach ($orders as $orderIndex => $order) {
                $comparison = LineComparison::of($entry, $order);
                if ($comparison->similarity() >= self::PAIRING_THRESHOLD) {
                    $distance = abs(self::position($entryIndex, \count($entries)) - self::position($orderIndex, \count($orders)));
                    $candidates[] = new PairingCandidate($entryIndex, $orderIndex, $comparison, $distance);
                }
            }
        }

        usort($candidates, static fn (PairingCandidate $a, PairingCandidate $b): int => [$b->comparison->similarity(), $a->distance] <=> [$a->comparison->similarity(), $b->distance]);

        return $candidates;
    }

    private static function position(int $index, int $count): float
    {
        return $count > 1 ? $index / ($count - 1) : 0.0;
    }
}
