<?php

declare(strict_types=1);

namespace App\Application\Integration\ConfigureConnection;

use App\Application\Integration\AuthorizingConnector;
use App\Application\Integration\ConnectionSession;
use App\Application\Integration\Connectors;
use App\Application\Transaction;
use App\Domain\Integration\ServiceConnectionRepository;

final readonly class UpdateConnectionHandler
{
    public function __construct(
        private Connectors $connectors,
        private ServiceConnectionRepository $connections,
        private ConnectionCredentials $credentials,
        private ConnectionSession $session,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(ConnectionSettings $settings): void
    {
        $connector = $this->connectors->get($settings->service);
        $description = $connector->describe();
        $connection = $this->connections->get($settings->service);

        $changed = $this->credentials->write($connection, $description, $settings->fields);
        if ($changed && $connector instanceof AuthorizingConnector) {
            $this->session->disconnect($connection);
        }
        $connection->choose($settings->salesContext ?? $connection->salesContext(), $settings->unknownItems ?? $connection->unknownItems());
        $this->transaction->commit();
    }
}
