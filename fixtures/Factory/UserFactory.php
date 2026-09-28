<?php

declare(strict_types=1);

namespace App\Fixtures\Factory;

use App\Application\Identity\PasswordHasher;
use App\Domain\Identity\User;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/** @extends PersistentObjectFactory<User> */
final class UserFactory extends PersistentObjectFactory
{
    public function __construct(private readonly PasswordHasher $hasher)
    {
        parent::__construct();
    }

    public static function class(): string
    {
        return User::class;
    }

    public function withPassword(string $password): static
    {
        return $this->afterInstantiate(fn (User $user) => $user->changePassword($this->hasher->hash($password)));
    }

    protected function defaults(): array
    {
        return [
            'email' => self::faker()->unique()->safeEmail(),
            'workspace' => WorkspaceFactory::new(),
        ];
    }

    protected function initialize(): static
    {
        return $this->instantiateWith(Instantiator::namedConstructor('invite')->disableHydration());
    }
}
