<?php

declare(strict_types=1);

namespace App\Application\Identity\CreateUser;

use App\Application\Identity\AccountMailer;
use App\Application\Identity\PasswordTokenIssuer;
use App\Application\Transaction;
use App\Domain\Identity\InvalidAccount;
use App\Domain\Identity\PasswordTokenPurpose;
use App\Domain\Identity\User;
use App\Domain\Identity\UserRepository;
use App\Domain\Identity\Workspace;
use App\Domain\Identity\WorkspaceRepository;

final readonly class CreateUserHandler
{
    public function __construct(
        private UserRepository $users,
        private WorkspaceRepository $workspaces,
        private PasswordTokenIssuer $tokens,
        private AccountMailer $mailer,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(CreateUser $command): User
    {
        $email = User::normalizeEmail($command->email);
        if (null !== $this->users->findByEmail($email)) {
            throw InvalidAccount::emailAlreadyUsed($email);
        }

        $workspace = $this->workspaces->findByName($command->workspaceName);
        if (null === $workspace) {
            $workspace = Workspace::create($command->workspaceName);
            $this->workspaces->add($workspace);
        }

        $user = User::invite($email, $workspace);
        $this->users->add($user);
        [$passwordToken, $token] = $this->tokens->issue($user, PasswordTokenPurpose::Invitation);
        $this->transaction->commit();

        $this->mailer->sendInvitation($passwordToken, $token);

        return $user;
    }
}
