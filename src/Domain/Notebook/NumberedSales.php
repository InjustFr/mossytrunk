<?php

declare(strict_types=1);

namespace App\Domain\Notebook;

final class NumberedSales implements SaleBoundaries
{
    public const string ORDER_NUMBER = '/^\s*(?:n°|no\.?|#)?\s*\(?\d{1,4}\s*[).:\-–—\/]\s*/iu';

    public function split(array $lines): array
    {
        $sales = [];
        $current = null;
        foreach ($lines as $line) {
            $rest = preg_replace(self::ORDER_NUMBER, '', $line, 1, $count) ?? $line;
            if (1 === $count) {
                if (null !== $current) {
                    $sales[] = $current;
                }
                $current = [$rest];
            } elseif (null !== $current && '' !== trim($line)) {
                $current[] = $line;
            }
        }
        if (null !== $current) {
            $sales[] = $current;
        }

        return $sales;
    }
}
