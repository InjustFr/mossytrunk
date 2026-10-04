<?php

declare(strict_types=1);

namespace App\Application\Integration\CompleteAuthorization;

use App\Application\Integration\AddedConnection;
use App\Application\Integration\ConnectionSession;
use App\Application\Integration\Connectors;
use App\Application\Transaction;

final readonly class CompleteAuthorizationHandler
{
    public function __construct(
        private Connectors $connectors,
        private AddedConnection $addedConnections,
        private ConnectionSession $session,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $service, string $code, string $codeVerifier, string $redirectUri): string
    {
        $connector = $this->connectors->authorizing($service);
        $connection = $this->addedConnections->of($connector);

        $authorization = $connector->authorize($this->session->configured($connection), $code, $codeVerifier, $redirectUri);
        $this->session->keepTokens($connection, $authorization->tokens);
        $connection->authorize($authorization->accountId, $authorization->accountName, $authorization->tokens->expiresAt);
        $this->transaction->commit();

        return $authorization->accountName;
    }
}
