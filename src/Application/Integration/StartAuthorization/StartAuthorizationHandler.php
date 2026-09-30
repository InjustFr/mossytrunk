<?php

declare(strict_types=1);

namespace App\Application\Integration\StartAuthorization;

use App\Application\Integration\ConnectionSession;
use App\Application\Integration\Connectors;
use App\Application\Integration\Exception\ServiceNotAdded;
use App\Domain\Integration\ServiceConnectionRepository;

final readonly class StartAuthorizationHandler
{
    public function __construct(
        private Connectors $connectors,
        private ServiceConnectionRepository $connections,
        private ConnectionSession $session,
    ) {
    }

    public function __invoke(string $service, string $redirectUri, string $state, string $codeChallenge): string
    {
        $connector = $this->connectors->authorizing($service);
        $connection = $this->connections->find($service) ?? throw new ServiceNotAdded($connector->describe()->label);

        return $connector->authorizationUrl($this->session->configured($connection), $redirectUri, $state, $codeChallenge);
    }
}
