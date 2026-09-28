<?php

declare(strict_types=1);

namespace App\Application\Identity\RequestPasswordReset;

use App\Application\Identity\AccountMailer;
use App\Application\Identity\PasswordTokenIssuer;
use App\Application\Transaction;
use App\Domain\Identity\PasswordTokenPurpose;
use App\Domain\Identity\UserRepository;

final readonly class RequestPasswordResetHandler
{
    public function __construct(
        private UserRepository $users,
        private PasswordTokenIssuer $tokens,
        private AccountMailer $mailer,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $email): void
    {
        $user = $this->users->findByEmail($email);
        if (null === $user) {
            return;
        }

        [$passwordToken, $token] = $this->tokens->issue($user, PasswordTokenPurpose::Reset);
        $this->transaction->commit();

        $this->mailer->sendPasswordReset($passwordToken, $token);
    }
}
