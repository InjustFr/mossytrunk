<?php

declare(strict_types=1);

namespace App\Application\Identity\SignIn;

use App\Application\Transaction;
use App\Application\Workspace\WorkspaceOpening;
use App\Domain\Identity\Exception\EmailAlreadyUsed;
use App\Domain\Identity\Exception\InvalidEmail;
use App\Domain\Identity\User;
use App\Domain\Identity\UserRepository;
use App\Domain\Identity\WorkspaceRepository;

final readonly class SignInHandler
{
    public function __construct(
        private UserRepository $users,
        private WorkspaceRepository $workspaces,
        private WorkspaceOpening $opening,
        private Transaction $transaction,
        private string $defaultWorkspace,
    ) {
    }

    public function __invoke(SignIn $command): User
    {
        $user = $this->users->findByAccountId($command->accountId) ?? $this->linkByEmail($command) ?? $this->join($command);
        if (null !== $command->email) {
            $this->followEmail($user, $command->email);
        }
        $this->transaction->commit();

        return $user;
    }

    private function linkByEmail(SignIn $command): ?User
    {
        if (null === $command->email) {
            return null;
        }

        $user = $this->users->findByEmail(User::normalizeEmail($command->email));
        if (null === $user) {
            return null;
        }
        if (null !== $user->accountId()) {
            throw new EmailAlreadyUsed($user->email());
        }
        $user->linkAccount($command->accountId);

        return $user;
    }

    private function join(SignIn $command): User
    {
        $email = User::normalizeEmail($command->email ?? throw new InvalidEmail(''));
        $workspace = $this->workspaces->findByName($this->defaultWorkspace) ?? $this->opening->open($this->defaultWorkspace);
        $user = User::join($command->accountId, $email, $workspace);
        $this->users->add($user);

        return $user;
    }

    private function followEmail(User $user, string $email): void
    {
        $email = User::normalizeEmail($email);
        if ($email === $user->email()) {
            return;
        }

        $holder = $this->users->findByEmail($email);
        if (null !== $holder && $holder !== $user) {
            throw new EmailAlreadyUsed($email);
        }
        $user->changeEmail($email);
    }
}
