<?php

declare(strict_types=1);

namespace App\Application;

/**
 * Port: persists every change made to the loaded aggregates during a use case.
 */
interface Transaction
{
    public function commit(): void;
}
