<?php

declare(strict_types=1);

namespace App\Application;

interface AtomicChange
{
    /**
     * @param callable(): void $change
     */
    public function apply(callable $change): void;
}
