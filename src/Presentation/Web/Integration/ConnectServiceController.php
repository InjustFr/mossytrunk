<?php

declare(strict_types=1);

namespace App\Presentation\Web\Integration;

use App\Application\Integration\StartAuthorization\StartAuthorizationHandler;
use App\Domain\Shared\Exception\DomainException;
use App\Presentation\RouteRequirement;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[Route('/settings/{service}/connect', name: 'service_connect', requirements: ['service' => RouteRequirement::SERVICE], methods: ['GET'])]
final readonly class ConnectServiceController
{
    public function __construct(private AuthorizationFlow $flow)
    {
    }

    public function __invoke(string $service, Request $request, StartAuthorizationHandler $startAuthorization): RedirectResponse
    {
        $verifier = self::base64Url(random_bytes(48));
        $state = bin2hex(random_bytes(16));
        $this->flow->remember($request, $service, $state, $verifier);

        try {
            return new RedirectResponse($startAuthorization($service, $this->flow->callbackUrl($service), $state, self::base64Url(hash('sha256', $verifier, true))));
        } catch (DomainException) {
            return $this->flow->outcome($service, 'unavailable');
        }
    }

    private static function base64Url(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }
}
