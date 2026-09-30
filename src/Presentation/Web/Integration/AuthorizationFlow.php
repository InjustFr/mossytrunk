<?php

declare(strict_types=1);

namespace App\Presentation\Web\Integration;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class AuthorizationFlow
{
    public function __construct(private UrlGeneratorInterface $urls)
    {
    }

    public function remember(Request $request, string $service, string $state, string $verifier): void
    {
        $request->getSession()->set(self::sessionKey($service), ['state' => $state, 'verifier' => $verifier]);
    }

    /**
     * @return array{state: string, verifier: string}|null
     */
    public function recall(Request $request, string $service): ?array
    {
        $pending = $request->getSession()->remove(self::sessionKey($service));
        $state = \is_array($pending) && \is_string($pending['state'] ?? null) ? $pending['state'] : null;
        $verifier = \is_array($pending) && \is_string($pending['verifier'] ?? null) ? $pending['verifier'] : null;

        return null === $state || null === $verifier ? null : ['state' => $state, 'verifier' => $verifier];
    }

    public function callbackUrl(string $service): string
    {
        return $this->urls->generate('service_callback', ['service' => $service], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    public function outcome(string $service, string $outcome): RedirectResponse
    {
        return new RedirectResponse('/settings?'.http_build_query(['service' => $service, 'connection' => $outcome]));
    }

    private static function sessionKey(string $service): string
    {
        return 'oauth_'.$service;
    }
}
