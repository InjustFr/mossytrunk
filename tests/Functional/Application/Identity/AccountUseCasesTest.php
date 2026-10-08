<?php

declare(strict_types=1);

namespace App\Tests\Functional\Application\Identity;

use App\Application\Identity\SignIn\SignIn;
use App\Application\Identity\SignIn\SignInHandler;
use App\Domain\Identity\Exception\EmailAlreadyUsed;
use App\Domain\Identity\Exception\InvalidEmail;
use App\Domain\Identity\User;
use App\Domain\Identity\UserRepository;
use App\Domain\Reference\ReferenceFormat;
use App\Domain\Reference\ReferenceKind;
use App\Domain\Sales\ChannelKind;
use App\Domain\Sales\SalesChannel;
use App\Tests\Support\ActsAsUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class AccountUseCasesTest extends KernelTestCase
{
    use ActsAsUser;

    public function testAFirstSignInOpensTheWorkspaceOfTheInvitation(): void
    {
        $user = $this->signIn(new SignIn('account-1', 'Louis@Example.com', 'Louis', ' Atelier Mousse '));

        self::assertSame(['account-1', 'louis@example.com', 'Atelier Mousse'], [$user->accountId(), $user->email(), $user->workspace()->name()]);
        $main = self::getContainer()->get('doctrine')->getRepository(SalesChannel::class)->findOneBy(['workspace' => $user->workspace(), 'main' => true]);
        self::assertInstanceOf(SalesChannel::class, $main);
        self::assertSame(ChannelKind::Market, $main->kind());
        self::assertCount(\count(ReferenceKind::cases()), self::getContainer()->get('doctrine')->getRepository(ReferenceFormat::class)->findBy(['workspace' => $user->workspace()]));
    }

    public function testNewAccountsJoinTheExistingWorkspaceOfTheirInvitation(): void
    {
        $member = self::createMember('Atelier Mousse');

        $user = $this->signIn(new SignIn('account-1', 'louis@example.com', 'Louis', 'Atelier Mousse'));

        self::assertTrue($member->workspace()->id()->equals($user->workspace()->id()));
    }

    public function testNewAccountsInvitedWithoutAWorkspaceOpenTheirOwn(): void
    {
        $member = self::createMember('Atelier Mousse');

        $user = $this->signIn(new SignIn('account-1', 'fern@example.com', 'Fern'));

        self::assertFalse($member->workspace()->id()->equals($user->workspace()->id()));
        self::assertSame('Fern', $user->workspace()->name());
        $main = self::getContainer()->get('doctrine')->getRepository(SalesChannel::class)->findOneBy(['workspace' => $user->workspace(), 'main' => true]);
        self::assertInstanceOf(SalesChannel::class, $main);
    }

    public function testAnOwnWorkspaceIsNamedAfterTheEmailWithoutANameAndNeverTakesAnExistingOne(): void
    {
        self::createMember('fern@example.com');
        $named = $this->signIn(new SignIn('account-1', 'fern@example.com'));
        $namesake = $this->signIn(new SignIn('account-2', 'fern2@example.com', 'fern@example.com'));

        self::assertSame(['fern@example.com (2)', 'fern@example.com (3)'], [$named->workspace()->name(), $namesake->workspace()->name()]);
    }

    public function testAnAccountWithoutEmailCannotJoin(): void
    {
        $this->expectExceptionObject(new InvalidEmail(''));

        $this->signIn(new SignIn('account-1', null));
    }

    public function testAnExistingUserIsLinkedByEmailAndKeepsTheirWorkspace(): void
    {
        $existing = self::createMemberFromBeforeAccounts('Autre atelier', 'louis@example.com');

        $user = $this->signIn(new SignIn('account-1', ' LOUIS@example.com'));

        self::assertSame($existing->id()->toBase32(), $user->id()->toBase32());
        self::assertSame(['account-1', 'Autre atelier'], [$user->accountId(), $user->workspace()->name()]);
    }

    public function testTheAccountIsFoundAgainAfterItsEmailChanges(): void
    {
        $first = $this->signIn(new SignIn('account-1', 'louis@example.com'));

        $again = $this->signIn(new SignIn('account-1', 'lou@example.com'));

        self::assertSame($first->id()->toBase32(), $again->id()->toBase32());
        self::assertSame('lou@example.com', $again->email());
    }

    public function testAnEmailAlreadyLinkedToAnotherAccountIsRefused(): void
    {
        $this->signIn(new SignIn('account-1', 'louis@example.com'));

        $this->expectExceptionObject(new EmailAlreadyUsed('louis@example.com'));

        $this->signIn(new SignIn('account-2', 'louis@example.com'));
    }

    public function testAnAccountCannotTakeTheEmailOfAnotherUser(): void
    {
        $this->signIn(new SignIn('account-1', 'louis@example.com'));
        $this->signIn(new SignIn('account-2', 'fern@example.com'));

        $this->expectExceptionObject(new EmailAlreadyUsed('louis@example.com'));

        $this->signIn(new SignIn('account-2', 'louis@example.com'));
    }

    private function signIn(SignIn $command): User
    {
        $user = self::getContainer()->get(SignInHandler::class)($command);
        self::getContainer()->get(EntityManagerInterface::class)->clear();

        return self::getContainer()->get(UserRepository::class)->get($user->id());
    }
}
