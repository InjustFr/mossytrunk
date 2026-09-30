<?php

declare(strict_types=1);

namespace App\Application\Integration\DisconnectService;

use App\Application\Integration\ConnectionSession;
use App\Application\Transaction;
use App\Domain\Integration\ServiceConnectionRepository;

final readonly class DisconnectServiceHandler
{
    public function __construct(
        private ServiceConnectionRepository $connections,
        private ConnectionSession $session,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $service): void
    {
        $this->session->disconnect($this->connections->get($service));
        $this->transaction->commit();
    }
}
