<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\Identity\Language;
use App\Domain\Identity\User;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Ulid;

final class SecurityUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    private function __construct(
        public readonly Ulid $id,
        public readonly string $email,
        private readonly ?string $passwordHash,
        public readonly Ulid $workspaceId,
        public readonly string $workspaceName,
        public readonly ?Language $language,
    ) {
    }

    public static function fromUser(User $user): self
    {
        return new self($user->id(), $user->email(), $user->passwordHash(), $user->workspace()->id(), $user->workspace()->name(), $user->language());
    }

    public function getUserIdentifier(): string
    {
        if ('' === $this->email) {
            throw new \LogicException('A signed-in user always has an email address.');
        }

        return $this->email;
    }

    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function getPassword(): ?string
    {
        return $this->passwordHash;
    }

    public function __serialize(): array
    {
        return [
            'id' => (string) $this->id,
            'email' => $this->email,
            'password' => null === $this->passwordHash ? null : hash('crc32c', $this->passwordHash),
            'workspaceId' => (string) $this->workspaceId,
            'workspaceName' => $this->workspaceName,
            'language' => $this->language?->value,
        ];
    }

    /** @param array{id: string, email: string, password: ?string, workspaceId: string, workspaceName: string, language?: ?string} $data */
    public function __unserialize(array $data): void
    {
        $this->id = Ulid::fromString($data['id']);
        $this->email = $data['email'];
        $this->passwordHash = $data['password'];
        $this->workspaceId = Ulid::fromString($data['workspaceId']);
        $this->workspaceName = $data['workspaceName'];
        $this->language = Language::tryFrom($data['language'] ?? '');
    }
}
