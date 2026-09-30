<?php

declare(strict_types=1);

namespace App\Application\Discount;

final readonly class ConditionDefinition
{
    /**
     * @param list<TargetDefinition> $targets
     */
    public function __construct(
        public int $quantity,
        public array $targets,
    ) {
    }
}
