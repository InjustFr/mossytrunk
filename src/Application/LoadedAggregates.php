<?php

declare(strict_types=1);

namespace App\Application;

/**
 * Port: the aggregates loaded so far, which later reads would otherwise reuse.
 */
interface LoadedAggregates
{
    public function forget(): void;
}
