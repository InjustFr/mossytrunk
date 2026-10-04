<?php

declare(strict_types=1);

namespace App\Application\Identity\CreateUser;

use App\Application\Identity\AccountMailer;
use App\Application\Identity\PasswordTokenIssuer;
use App\Application\Transaction;
use App\Application\Workspace\WorkspaceOpening;
use App\Domain\Identity\Exception\EmailAlreadyUsed;
use App\Domain\Identity\PasswordTokenPurpose;
use App\Domain\Identity\User;
use App\Domain\Identity\UserRepository;
use App\Domain\Identity\WorkspaceRepository;

final readonly class CreateUserHandler
{
    public function __construct(
        private UserRepository $users,
        private WorkspaceRepository $workspaces,
        private PasswordTokenIssuer $tokens,
        private AccountMailer $mailer,
        private WorkspaceOpening $opening,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(CreateUser $command): User
    {
        $email = User::normalizeEmail($command->email);
        if (null !== $this->users->findByEmail($email)) {
            throw new EmailAlreadyUsed($email);
        }

        $workspace = $this->workspaces->findByName($command->workspaceName) ?? $this->opening->open($command->workspaceName);

        $user = User::invite($email, $workspace);
        $this->users->add($user);
        [$passwordToken, $token] = $this->tokens->issue($user, PasswordTokenPurpose::Invitation);
        $this->transaction->commit();

        $this->mailer->sendInvitation($passwordToken, $token);

        return $user;
    }
}
