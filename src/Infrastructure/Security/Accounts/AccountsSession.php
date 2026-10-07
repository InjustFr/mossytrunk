<?php

declare(strict_types=1);

namespace App\Infrastructure\Security\Accounts;

use Symfony\Component\HttpFoundation\RequestStack;

final readonly class AccountsSession
{
    private const string ID_TOKEN = '_accounts_id_token';

    public function __construct(private RequestStack $requestStack)
    {
    }

    public function remember(?string $idToken): void
    {
        $session = $this->requestStack->getSession();
        if (null === $idToken) {
            $session->remove(self::ID_TOKEN);

            return;
        }

        $session->set(self::ID_TOKEN, $idToken);
    }

    public function idToken(): ?string
    {
        $idToken = $this->requestStack->getSession()->get(self::ID_TOKEN);

        return \is_string($idToken) && '' !== $idToken ? $idToken : null;
    }
}
