<?php

declare(strict_types=1);

namespace App\Application\Identity;

use App\Domain\Identity\Exception\UnknownPasswordToken;
use App\Domain\Identity\PasswordToken;
use App\Domain\Identity\PasswordTokenPurpose;
use App\Domain\Identity\PasswordTokenRepository;
use App\Domain\Identity\User;
use Psr\Clock\ClockInterface;

final readonly class PasswordTokenIssuer
{
    public function __construct(
        private PasswordTokenRepository $tokens,
        private ClockInterface $clock,
    ) {
    }

    /** @return array{PasswordToken, string} */
    public function issue(User $user, PasswordTokenPurpose $purpose): array
    {
        $this->tokens->removeAllFor($user);

        $selector = bin2hex(random_bytes(PasswordToken::SELECTOR_LENGTH / 2));
        $verifier = bin2hex(random_bytes(24));
        $passwordToken = PasswordToken::issue($user, $purpose, $selector, $verifier, $this->now());
        $this->tokens->add($passwordToken);

        return [$passwordToken, $selector.$verifier];
    }

    /** @return array{PasswordToken, string} */
    public function resolve(string $token): array
    {
        $selector = substr($token, 0, PasswordToken::SELECTOR_LENGTH);
        $verifier = substr($token, PasswordToken::SELECTOR_LENGTH);

        $passwordToken = '' === $verifier ? null : $this->tokens->findBySelector($selector);
        if (null === $passwordToken) {
            throw new UnknownPasswordToken();
        }
        $passwordToken->verify($verifier, $this->now());

        return [$passwordToken, $verifier];
    }

    public function now(): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromInterface($this->clock->now());
    }
}
