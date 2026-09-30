<?php

declare(strict_types=1);

namespace App\Application\Integration\ConfigureConnection;

use App\Application\Integration\Connectors;
use App\Application\Transaction;
use App\Application\WorkspaceContext;
use App\Domain\Identity\WorkspaceRepository;
use App\Domain\Integration\Exception\ServiceAlreadyAdded;
use App\Domain\Integration\ServiceConnection;
use App\Domain\Integration\ServiceConnectionRepository;

final readonly class AddConnectionHandler
{
    public function __construct(
        private Connectors $connectors,
        private ServiceConnectionRepository $connections,
        private ConnectionCredentials $credentials,
        private WorkspaceContext $workspace,
        private WorkspaceRepository $workspaces,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(ConnectionSettings $settings): void
    {
        $description = $this->connectors->get($settings->service)->describe();
        if (null !== $this->connections->find($settings->service)) {
            throw new ServiceAlreadyAdded($description->label);
        }

        $connection = ServiceConnection::create(
            $this->workspaces->get($this->workspace->current()->id()),
            $description->key,
            [],
            $settings->salesContext ?? $description->defaultSalesContext,
            $settings->unknownItems ?? $description->defaultUnknownItems,
        );
        $this->credentials->write($connection, $description, $settings->fields);
        $this->connections->add($connection);
        $this->transaction->commit();
    }
}
