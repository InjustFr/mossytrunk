<?php

declare(strict_types=1);

namespace App\Domain\Notebook;

interface SaleBoundaries
{
    /**
     * @param list<string> $lines
     *
     * @return list<list<string>>
     */
    public function split(array $lines): array;
}
