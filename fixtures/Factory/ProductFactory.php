<?php

declare(strict_types=1);

namespace App\Fixtures\Factory;

use App\Domain\Product\Product;
use App\Domain\Shared\Money;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * Builds products through Product::create() — entities have no setters.
 *
 * @extends PersistentObjectFactory<Product>
 */
final class ProductFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Product::class;
    }

    protected function defaults(): array
    {
        return [
            'workspace' => WorkspaceFactory::new(),
            'reference' => strtoupper(self::faker()->unique()->bothify('PRD-####')),
            'name' => ucfirst(self::faker()->words(2, true)),
            'sellingPrice' => Money::cents(self::faker()->randomElement([400, 800, 1_200, 1_500, 2_000, 2_500])),
            'variants' => [],
        ];
    }

    protected function initialize(): static
    {
        return $this->instantiateWith(Instantiator::namedConstructor('create')->disableHydration());
    }
}
