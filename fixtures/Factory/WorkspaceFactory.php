<?php

declare(strict_types=1);

namespace App\Fixtures\Factory;

use App\Domain\Identity\Workspace;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/** @extends PersistentObjectFactory<Workspace> */
final class WorkspaceFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return Workspace::class;
    }

    protected function defaults(): array
    {
        return ['name' => 'Atelier '.self::faker()->unique()->lastName()];
    }

    protected function initialize(): static
    {
        return $this->instantiateWith(Instantiator::namedConstructor('create')->disableHydration());
    }
}
