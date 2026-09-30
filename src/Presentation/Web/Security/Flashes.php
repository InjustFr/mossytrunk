<?php

declare(strict_types=1);

namespace App\Presentation\Web\Security;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;

final readonly class Flashes
{
    public function __construct(private RequestStack $requestStack)
    {
    }

    public function take(string $type): ?string
    {
        $session = $this->requestStack->getSession();
        $messages = $session instanceof FlashBagAwareSessionInterface ? $session->getFlashBag()->get($type) : [];

        $message = $messages[0] ?? null;

        return \is_string($message) ? $message : null;
    }
}
