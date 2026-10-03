<?php

declare(strict_types=1);

namespace App\Domain\Notebook;

enum SaleSeparation: string
{
    case Numbered = 'numbered';
    case OnePerLine = 'line';
    case Gap = 'gap';

    public function boundaries(): SaleBoundaries
    {
        return match ($this) {
            self::Numbered => new NumberedSales(),
            self::OnePerLine => new OneSalePerLine(),
            self::Gap => new GapSeparatedSales(),
        };
    }
}
