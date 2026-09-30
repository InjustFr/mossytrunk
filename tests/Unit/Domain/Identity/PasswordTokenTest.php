<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Identity;

use App\Domain\Identity\Exception\PasswordTokenAlreadyUsed;
use App\Domain\Identity\Exception\PasswordTokenExpired;
use App\Domain\Identity\Exception\UnknownPasswordToken;
use App\Domain\Identity\PasswordToken;
use App\Domain\Identity\PasswordTokenPurpose;
use App\Domain\Identity\User;
use App\Domain\Identity\Workspace;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PasswordTokenTest extends TestCase
{
    private const string SELECTOR = 'abcdefabcdefabcdefabcdef';

    public function testLifetimeDependsOnPurpose(): void
    {
        $now = new \DateTimeImmutable('2030-01-01 10:00');

        self::assertEquals(new \DateTimeImmutable('2030-01-08 10:00'), $this->token(PasswordTokenPurpose::Invitation, $now)->expiresAt());
        self::assertEquals(new \DateTimeImmutable('2030-01-01 11:00'), $this->token(PasswordTokenPurpose::Reset, $now)->expiresAt());
    }

    public function testCanBeConsumedOnce(): void
    {
        $now = new \DateTimeImmutable('2030-01-01 10:00');
        $token = $this->token(PasswordTokenPurpose::Reset, $now);

        $token->consume('secret', $now);

        $this->expectExceptionObject(new PasswordTokenAlreadyUsed());
        $token->consume('secret', $now);
    }

    public function testRejectsWrongVerifier(): void
    {
        $now = new \DateTimeImmutable('2030-01-01 10:00');

        $this->expectExceptionObject(new UnknownPasswordToken());
        $this->token(PasswordTokenPurpose::Reset, $now)->verify('guess', $now);
    }

    #[DataProvider('expiredMoments')]
    public function testRejectsExpiredToken(string $moment): void
    {
        $token = $this->token(PasswordTokenPurpose::Reset, new \DateTimeImmutable('2030-01-01 10:00'));

        $this->expectExceptionObject(new PasswordTokenExpired());
        $token->verify('secret', new \DateTimeImmutable($moment));
    }

    /** @return iterable<array{string}> */
    public static function expiredMoments(): iterable
    {
        yield 'at expiry' => ['2030-01-01 11:00'];
        yield 'after expiry' => ['2030-01-02 09:00'];
    }

    public function testStoresOnlyAHashOfTheVerifier(): void
    {
        $token = $this->token(PasswordTokenPurpose::Reset, new \DateTimeImmutable());

        self::assertStringNotContainsString('secret', serialize((array) $token));
    }

    private function token(PasswordTokenPurpose $purpose, \DateTimeImmutable $now): PasswordToken
    {
        return PasswordToken::issue(User::invite('louis@example.com', Workspace::create('Atelier')), $purpose, self::SELECTOR, 'secret', $now);
    }
}
