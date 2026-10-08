<?php

declare(strict_types=1);

namespace App\Application\Identity\SignIn;

use App\Application\Transaction;
use App\Application\Workspace\WorkspaceOpening;
use App\Domain\Identity\Exception\EmailAlreadyUsed;
use App\Domain\Identity\Exception\InvalidEmail;
use App\Domain\Identity\User;
use App\Domain\Identity\UserRepository;
use App\Domain\Identity\Workspace;
use App\Domain\Identity\WorkspaceRepository;

final readonly class SignInHandler
{
    private const int OWN_WORKSPACE_NAME_LENGTH = 90;

    public function __construct(
        private UserRepository $users,
        private WorkspaceRepository $workspaces,
        private WorkspaceOpening $opening,
        private Transaction $transaction,
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
        $user = User::join($command->accountId, $email, $this->workspaceToJoin($command, $email));
        $this->users->add($user);

        return $user;
    }

    /**
     * The workspace the account was invited to (claim set in mossyleaf accounts), opened when it does not exist yet;
     * without one, a workspace of their own named after them.
     */
    private function workspaceToJoin(SignIn $command, string $email): Workspace
    {
        $invited = trim($command->workspace ?? '');
        if ('' !== $invited) {
            return $this->workspaces->findByName($invited) ?? $this->opening->open($invited);
        }

        $base = mb_substr(trim($command->name ?? '') ?: $email, 0, self::OWN_WORKSPACE_NAME_LENGTH);
        $name = $base;
        for ($rank = 2; null !== $this->workspaces->findByName($name); ++$rank) {
            $name = \sprintf('%s (%d)', $base, $rank);
        }

        return $this->opening->open($name);
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
