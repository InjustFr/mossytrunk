<?php

declare(strict_types=1);

namespace App\Fixtures\Factory;

use App\Domain\Discount\DiscountRule;
use App\Domain\Shared\Money;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<DiscountRule>
 */
final class DiscountRuleFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return DiscountRule::class;
    }

    protected function defaults(): array
    {
        return [
            'name' => 'Lot',
            'eligibleProducts' => [],
            'bundleSize' => 3,
            'bundlePrice' => Money::cents(1_000),
        ];
    }

    protected function initialize(): static
    {
        return $this->instantiateWith(Instantiator::namedConstructor('create')->disableHydration());
    }
}
