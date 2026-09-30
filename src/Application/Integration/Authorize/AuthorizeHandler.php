<?php

declare(strict_types=1);

namespace App\Application\Integration\Authorize;

use App\Application\Integration\AuthorizingConnector;
use App\Application\Integration\ConnectionSession;
use App\Application\Integration\Connectors;
use App\Application\Integration\Exception\ServiceNotAdded;
use App\Application\Integration\Exception\ServiceNotAuthorizing;
use App\Application\Transaction;
use App\Domain\Integration\ServiceConnectionRepository;

final readonly class AuthorizeHandler
{
    public function __construct(
        private Connectors $connectors,
        private ServiceConnectionRepository $connections,
        private ConnectionSession $session,
        private Transaction $transaction,
    ) {
    }

    public function authorizationUrl(string $service, string $redirectUri, string $state, string $codeChallenge): string
    {
        $connector = $this->connector($service);
        $connection = $this->connections->find($service) ?? throw new ServiceNotAdded($connector->describe()->label);

        return $connector->authorizationUrl($this->session->configured($connection), $redirectUri, $state, $codeChallenge);
    }

    public function complete(string $service, string $code, string $codeVerifier, string $redirectUri): string
    {
        $connector = $this->connector($service);
        $connection = $this->connections->find($service) ?? throw new ServiceNotAdded($connector->describe()->label);

        $authorization = $connector->authorize($this->session->configured($connection), $code, $codeVerifier, $redirectUri);
        $this->session->keepTokens($connection, $authorization->tokens);
        $connection->authorize($authorization->accountId, $authorization->accountName, $authorization->tokens->expiresAt);
        $this->transaction->commit();

        return $authorization->accountName;
    }

    public function disconnect(string $service): void
    {
        $this->session->disconnect($this->connections->get($service));
        $this->transaction->commit();
    }

    private function connector(string $service): AuthorizingConnector
    {
        $connector = $this->connectors->get($service);
        if (!$connector instanceof AuthorizingConnector) {
            throw new ServiceNotAuthorizing($connector->describe()->label);
        }

        return $connector;
    }
}
