<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Identity;

use App\Domain\Identity\Exception\InvalidAccount;
use App\Domain\Identity\Exception\InvalidEmail;
use App\Domain\Identity\User;
use App\Domain\Identity\Workspace;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function testEmailIsNormalized(): void
    {
        $user = User::join('account', '  Louis@Example.COM ', Workspace::create('Atelier'));

        self::assertSame('louis@example.com', $user->email());
    }

    public function testRejectsInvalidEmail(): void
    {
        $this->expectException(InvalidAccount::class);

        User::join('account', 'not-an-email', Workspace::create('Atelier'));
    }

    public function testFollowsItsMossyleafAccount(): void
    {
        $user = User::join('account', 'louis@example.com', Workspace::create('Atelier'));
        self::assertSame('account', $user->accountId());

        $user->linkAccount('another-account');
        $user->changeEmail(' Lou@Example.com ');

        self::assertSame(['another-account', 'lou@example.com'], [$user->accountId(), $user->email()]);
    }

    public function testChangedEmailMustBeValid(): void
    {
        $user = User::join('account', 'louis@example.com', Workspace::create('Atelier'));

        $this->expectExceptionObject(new InvalidEmail('lou'));

        $user->changeEmail('lou');
    }

    public function testWorkspaceNameIsRequired(): void
    {
        $this->expectException(InvalidAccount::class);

        Workspace::create('  ');
    }
}
