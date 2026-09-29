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
            'name' => ucfirst(self::faker()->word().' '.self::faker()->word()),
            'sellingPrice' => Money::cents(100 * self::faker()->numberBetween(4, 25)),
            'variants' => [],
        ];
    }

    protected function initialize(): static
    {
        return $this->instantiateWith(Instantiator::namedConstructor('create')->disableHydration());
    }
}
