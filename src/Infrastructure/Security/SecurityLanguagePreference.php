<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Application\Identity\LanguagePreference;
use App\Domain\Identity\Language;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final readonly class SecurityLanguagePreference implements LanguagePreference
{
    public function __construct(private TokenStorageInterface $tokens)
    {
    }

    public function ofSignedInUser(): ?Language
    {
        $user = $this->tokens->getToken()?->getUser();

        return $user instanceof SecurityUser ? $user->language : null;
    }
}
