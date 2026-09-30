<?php

declare(strict_types=1);

namespace App\Fixtures\Factory;

use App\Domain\Product\ProductType;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<ProductType>
 */
final class ProductTypeFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return ProductType::class;
    }

    protected function defaults(): array
    {
        $name = ucfirst(self::faker()->unique()->word());

        return ['workspace' => WorkspaceFactory::new(), 'name' => $name, 'code' => ProductType::codeFor($name), 'color' => self::faker()->randomElement(ProductType::PALETTE)];
    }

    protected function initialize(): static
    {
        return $this->instantiateWith(Instantiator::namedConstructor('create')->disableHydration());
    }
}
