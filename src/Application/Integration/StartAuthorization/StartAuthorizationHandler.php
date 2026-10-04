<?php

declare(strict_types=1);

namespace App\Application\Integration\StartAuthorization;

use App\Application\Integration\AddedConnection;
use App\Application\Integration\ConnectionSession;
use App\Application\Integration\Connectors;

final readonly class StartAuthorizationHandler
{
    public function __construct(
        private Connectors $connectors,
        private AddedConnection $addedConnections,
        private ConnectionSession $session,
    ) {
    }

    public function __invoke(string $service, string $redirectUri, string $state, string $codeChallenge): string
    {
        $connector = $this->connectors->authorizing($service);
        $connection = $this->addedConnections->of($connector);

        return $connector->authorizationUrl($this->session->configured($connection), $redirectUri, $state, $codeChallenge);
    }
}
