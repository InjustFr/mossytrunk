<?php

declare(strict_types=1);

namespace App\Application\Design\WorkOnCollection;

use App\Application\Transaction;
use App\Domain\Design\DesignCollectionRepository;
use Symfony\Component\Uid\Ulid;

final readonly class WorkOnCollectionHandler
{
    public function __construct(
        private DesignCollectionRepository $collections,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $collectionId, bool $current): void
    {
        $this->collections->get(Ulid::fromString($collectionId))->workOn($current);
        $this->transaction->commit();
    }
}
