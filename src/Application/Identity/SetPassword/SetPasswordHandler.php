<?php

declare(strict_types=1);

namespace App\Application\Identity\SetPassword;

use App\Application\Identity\PasswordHasher;
use App\Application\Identity\PasswordTokenIssuer;
use App\Application\Transaction;
use App\Domain\Identity\Exception\PasswordTooShort;
use App\Domain\Identity\PasswordTokenRepository;
use App\Domain\Identity\User;

final readonly class SetPasswordHandler
{
    public const int MIN_LENGTH = 12;

    public function __construct(
        private PasswordTokenIssuer $issuer,
        private PasswordTokenRepository $tokens,
        private PasswordHasher $hasher,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $token, string $password): User
    {
        [$passwordToken, $verifier] = $this->issuer->resolve($token);

        if (mb_strlen($password) < self::MIN_LENGTH) {
            throw new PasswordTooShort(self::MIN_LENGTH);
        }

        $passwordToken->consume($verifier, $this->issuer->now());
        $user = $passwordToken->user();
        $user->changePassword($this->hasher->hash($password));
        $this->tokens->removeAllFor($user);
        $this->transaction->commit();

        return $user;
    }
}
