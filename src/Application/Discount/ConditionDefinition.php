<?php

declare(strict_types=1);

namespace App\Application\Discount;

final readonly class ConditionDefinition
{
    public const string PRODUCT = 'product';
    public const string TYPE = 'type';

    public function __construct(
        public string $kind,
        public string $targetId,
        public int $quantity,
        public ?string $variant = null,
    ) {
    }
}
