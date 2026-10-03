<?php

declare(strict_types=1);

namespace App\Domain\Notebook;

final class GapSeparatedSales implements SaleBoundaries
{
    private const string SEPARATOR = '/^[\s\-–—_=~*.]*$/u';

    public function split(array $lines): array
    {
        $sales = [];
        $current = [];
        foreach ($lines as $line) {
            if (1 === preg_match(self::SEPARATOR, $line)) {
                if ([] !== $current) {
                    $sales[] = $current;
                }
                $current = [];
            } else {
                $current[] = preg_replace(NumberedSales::ORDER_NUMBER, '', $line, 1) ?? $line;
            }
        }
        if ([] !== $current) {
            $sales[] = $current;
        }

        return $sales;
    }
}
