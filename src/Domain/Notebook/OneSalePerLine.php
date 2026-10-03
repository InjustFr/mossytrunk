<?php

declare(strict_types=1);

namespace App\Domain\Notebook;

final class OneSalePerLine implements SaleBoundaries
{
    public function split(array $lines): array
    {
        $sales = [];
        foreach ($lines as $line) {
            if ('' !== trim($line)) {
                $sales[] = [preg_replace(NumberedSales::ORDER_NUMBER, '', $line, 1) ?? $line];
            }
        }

        return $sales;
    }
}
