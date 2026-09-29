<?php

declare(strict_types=1);

namespace App\Tests\Functional\Application\Identity;

use App\Application\Identity\CreateUser\CreateUser;
use App\Application\Identity\CreateUser\CreateUserHandler;
use App\Application\Identity\RequestPasswordReset\RequestPasswordResetHandler;
use App\Application\Identity\SetPassword\SetPasswordHandler;
use App\Domain\Identity\InvalidAccount;
use App\Domain\Identity\InvalidPasswordToken;
use App\Domain\Identity\User;
use App\Domain\Identity\UserRepository;
use App\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\FrameworkBundle\Test\MailerAssertionsTrait;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;

final class AccountUseCasesTest extends KernelTestCase
{
    use ClockSensitiveTrait;
    use MailerAssertionsTrait;

    public function testCreatingAUserSendsAnInvitation(): void
    {
        $user = $this->createUser('Louis@Example.com', 'Atelier');

        self::assertSame('louis@example.com', $user->email());
        self::assertSame('Atelier', $user->workspace()->name());
        self::assertFalse($user->hasPassword());
        self::assertEmailCount(1);
        $email = self::getMailerMessage();
        self::assertNotNull($email);
        self::assertEmailAddressContains($email, 'To', 'louis@example.com');
        self::assertEmailHtmlBodyContains($email, 'Atelier');
    }

    public function testUsersJoinAnExistingWorkspaceByName(): void
    {
        $first = $this->createUser('a@example.com', 'Atelier');
        $second = $this->createUser('b@example.com', 'Atelier');

        self::assertTrue($first->workspace()->id()->equals($second->workspace()->id()));
    }

    public function testEmailIsUnique(): void
    {
        $this->createUser('louis@example.com', 'Atelier');

        $this->expectException(InvalidAccount::class);
        $this->createUser('LOUIS@example.com', 'Autre');
    }

    public function testInvitationLinkSetsThePasswordOnce(): void
    {
        $this->createUser('louis@example.com', 'Atelier');
        $token = $this->tokenFromLastEmail();

        $user = self::getContainer()->get(SetPasswordHandler::class)($token, 'correct horse battery');

        self::assertTrue(self::getContainer()->get(PasswordHasherFactoryInterface::class)->getPasswordHasher(SecurityUser::class)->verify((string) $user->passwordHash(), 'correct horse battery'));
        $this->expectException(InvalidPasswordToken::class);
        self::getContainer()->get(SetPasswordHandler::class)($token, 'another password');
    }

    public function testPasswordMustBeLongEnough(): void
    {
        $this->createUser('louis@example.com', 'Atelier');

        $this->expectException(InvalidAccount::class);
        self::getContainer()->get(SetPasswordHandler::class)($this->tokenFromLastEmail(), 'short');
    }

    public function testResetLinkExpiresAfterOneHour(): void
    {
        $clock = self::mockTime('2030-01-01 10:00');
        $this->createUser('louis@example.com', 'Atelier');
        self::getContainer()->get(RequestPasswordResetHandler::class)('louis@example.com');
        $token = $this->tokenFromLastEmail();

        $clock->sleep(3_600);

        $this->expectExceptionObject(InvalidPasswordToken::expired());
        self::getContainer()->get(SetPasswordHandler::class)($token, 'correct horse battery');
    }

    public function testNewLinkRevokesThePreviousOne(): void
    {
        $this->createUser('louis@example.com', 'Atelier');
        $invitation = $this->tokenFromLastEmail();

        self::getContainer()->get(RequestPasswordResetHandler::class)('louis@example.com');

        $this->expectExceptionObject(InvalidPasswordToken::invalid());
        self::getContainer()->get(SetPasswordHandler::class)($invitation, 'correct horse battery');
    }

    public function testResetForUnknownEmailSendsNothing(): void
    {
        self::getContainer()->get(RequestPasswordResetHandler::class)('nobody@example.com');

        self::assertEmailCount(0);
        self::assertNull(self::getContainer()->get(UserRepository::class)->findByEmail('nobody@example.com'));
    }

    private function createUser(string $email, string $workspace): User
    {
        return self::getContainer()->get(CreateUserHandler::class)(new CreateUser($email, $workspace));
    }

    private function tokenFromLastEmail(): string
    {
        $messages = self::getMailerMessages();
        $email = end($messages);
        self::assertInstanceOf(Email::class, $email);
        self::assertSame(1, preg_match('#/mot-de-passe/definir/([0-9a-f]+)#', (string) $email->getHtmlBody(), $matches));

        return $matches[1];
    }
}
