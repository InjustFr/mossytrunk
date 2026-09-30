<?php

declare(strict_types=1);

namespace App\Presentation\Web\Integration;

use App\Application\Integration\CompleteAuthorization\CompleteAuthorizationHandler;
use App\Domain\Shared\Exception\DomainException;
use App\Presentation\RouteRequirement;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[Route('/settings/{service}/callback', name: 'service_callback', requirements: ['service' => RouteRequirement::SERVICE], methods: ['GET'])]
final readonly class ServiceCallbackController
{
    public function __construct(private AuthorizationFlow $flow)
    {
    }

    public function __invoke(string $service, Request $request, CompleteAuthorizationHandler $completeAuthorization): RedirectResponse
    {
        $pending = $this->flow->recall($request, $service);
        $code = $request->query->getString('code');

        if (null === $pending || '' === $code || !hash_equals($pending['state'], $request->query->getString('state'))) {
            return $this->flow->outcome($service, 'refused');
        }

        try {
            $completeAuthorization($service, $code, $pending['verifier'], $this->flow->callbackUrl($service));
        } catch (DomainException) {
            return $this->flow->outcome($service, 'error');
        }

        return $this->flow->outcome($service, 'connected');
    }
}
