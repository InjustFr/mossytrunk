<?php

declare(strict_types=1);

namespace App\Application\Integration\RemoveConnection;

use App\Application\Integration\ConnectionSession;
use App\Application\Transaction;
use App\Domain\Integration\ServiceConnectionRepository;

final readonly class RemoveConnectionHandler
{
    public function __construct(
        private ServiceConnectionRepository $connections,
        private ConnectionSession $session,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $service): void
    {
        $connection = $this->connections->get($service);
        $this->session->forget($connection);
        $this->connections->remove($connection);
        $this->transaction->commit();
    }
}
