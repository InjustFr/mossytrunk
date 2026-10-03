<?php

declare(strict_types=1);

namespace App\Domain\Notebook;

final readonly class LineComparison
{
    /**
     * @param list<Gap> $onlyNoted
     * @param list<Gap> $onlySold
     */
    private function __construct(
        public int $matched,
        public int $sameProduct,
        public int $noted,
        public int $sold,
        public array $onlyNoted,
        public array $onlySold,
    ) {
    }

    public static function of(NotebookEntry $entry, RecordedOrder $order): self
    {
        $left = array_map(static fn (RecordedLine $line): int => $line->quantity, $order->lines);
        $matched = 0;
        $onlyNoted = [];
        $unmatched = [];

        foreach (self::mostPreciseFirst($entry->lines) as $line) {
            $wanted = $line->quantity;
            foreach ($order->lines as $index => $sold) {
                if ($wanted > 0 && $left[$index] > 0 && $line->accepts($sold)) {
                    $taken = min($wanted, $left[$index]);
                    $left[$index] -= $taken;
                    $wanted -= $taken;
                    $matched += $taken;
                }
            }
            if ($wanted > 0) {
                $onlyNoted[] = new Gap($line->label, $wanted);
                $unmatched[] = [$line, $wanted];
            }
        }

        $sameProduct = self::sameProductUnits($unmatched, $order->lines, $left);

        $onlySold = [];
        foreach ($order->lines as $index => $sold) {
            if ($left[$index] > 0) {
                $onlySold[] = new Gap($sold->label, $left[$index]);
            }
        }

        return new self($matched, $sameProduct, $entry->units(), $order->units(), $onlyNoted, $onlySold);
    }

    public function similarity(): float
    {
        return (2 * $this->matched + $this->sameProduct) / ($this->noted + $this->sold);
    }

    public function isExact(): bool
    {
        return [] === $this->onlyNoted && [] === $this->onlySold;
    }

    /**
     * @param list<array{NotebookLine, int}> $unmatched
     * @param list<RecordedLine>             $sold
     * @param list<int>                      $left
     */
    private static function sameProductUnits(array $unmatched, array $sold, array $left): int
    {
        $units = 0;
        foreach ($unmatched as [$line, $wanted]) {
            foreach ($sold as $index => $soldLine) {
                if ($wanted > 0 && $left[$index] > 0 && null !== $line->productId && null !== $soldLine->productId && $line->productId->equals($soldLine->productId)) {
                    $taken = min($wanted, $left[$index]);
                    $left[$index] -= $taken;
                    $wanted -= $taken;
                    $units += $taken;
                }
            }
        }

        return $units;
    }

    /**
     * @param list<NotebookLine> $lines
     *
     * @return list<NotebookLine>
     */
    private static function mostPreciseFirst(array $lines): array
    {
        usort($lines, static fn (NotebookLine $a, NotebookLine $b): int => self::precision($b) <=> self::precision($a));

        return $lines;
    }

    private static function precision(NotebookLine $line): int
    {
        return match (true) {
            $line->namesProduct() && null !== $line->variant => 3,
            $line->namesProduct() => 2,
            $line->namesOnlyType() => 1,
            default => 0,
        };
    }
}
