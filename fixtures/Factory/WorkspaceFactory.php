<?php

declare(strict_types=1);

namespace App\Fixtures\Factory;

use App\Domain\Identity\Workspace;
use App\Domain\Sales\SalesChannel;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

use function Zenstruck\Foundry\Persistence\save;

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
        return $this->instantiateWith(Instantiator::namedConstructor('create')->disableHydration())
            ->afterPersist(static function (Workspace $workspace): void {
                save(SalesChannel::main($workspace, 'Marchés'));
            });
    }
}
