<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\Identity\Language;
use App\Domain\Identity\Theme;
use App\Domain\Identity\User;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Ulid;

final class SecurityUser implements UserInterface
{
    private function __construct(
        public readonly Ulid $id,
        public readonly string $email,
        public readonly Ulid $workspaceId,
        public readonly string $workspaceName,
        public readonly ?Language $language,
        public readonly ?Theme $theme,
    ) {
    }

    public static function fromUser(User $user): self
    {
        return new self($user->id(), $user->email(), $user->workspace()->id(), $user->workspace()->name(), $user->language(), $user->theme());
    }

    public function getUserIdentifier(): string
    {
        return $this->id->toBase32();
    }

    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function __serialize(): array
    {
        return [
            'id' => (string) $this->id,
            'email' => $this->email,
            'workspaceId' => (string) $this->workspaceId,
            'workspaceName' => $this->workspaceName,
            'language' => $this->language?->value,
            'theme' => null === $this->theme ? null : [$this->theme->background, $this->theme->accent],
        ];
    }

    /** @param array{id: string, email: string, workspaceId: string, workspaceName: string, language?: ?string, theme?: ?array{string, string}} $data */
    public function __unserialize(array $data): void
    {
        $this->id = Ulid::fromString($data['id']);
        $this->email = $data['email'];
        $this->workspaceId = Ulid::fromString($data['workspaceId']);
        $this->workspaceName = $data['workspaceName'];
        $this->language = Language::tryFrom($data['language'] ?? '');
        $this->theme = isset($data['theme']) ? Theme::of(...$data['theme']) : null;
    }
}
