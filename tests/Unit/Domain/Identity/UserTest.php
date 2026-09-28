<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Identity;

use App\Domain\Identity\InvalidAccount;
use App\Domain\Identity\User;
use App\Domain\Identity\Workspace;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function testEmailIsNormalized(): void
    {
        $user = User::invite('  Louis@Example.COM ', Workspace::create('Atelier'));

        self::assertSame('louis@example.com', $user->email());
    }

    public function testRejectsInvalidEmail(): void
    {
        $this->expectException(InvalidAccount::class);

        User::invite('not-an-email', Workspace::create('Atelier'));
    }

    public function testInvitedUserHasNoPasswordUntilSet(): void
    {
        $user = User::invite('louis@example.com', Workspace::create('Atelier'));
        self::assertFalse($user->hasPassword());

        $user->changePassword('hash');

        self::assertTrue($user->hasPassword());
    }

    public function testWorkspaceNameIsRequired(): void
    {
        $this->expectException(InvalidAccount::class);

        Workspace::create('  ');
    }
}
