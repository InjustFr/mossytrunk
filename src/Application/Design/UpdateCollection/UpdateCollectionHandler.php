<?php

declare(strict_types=1);

namespace App\Application\Design\UpdateCollection;

use App\Application\Transaction;
use App\Domain\Design\DesignCollectionRepository;
use Symfony\Component\Uid\Ulid;

final readonly class UpdateCollectionHandler
{
    public function __construct(
        private DesignCollectionRepository $collections,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $collectionId, string $name, ?string $description): void
    {
        $this->collections->get(Ulid::fromString($collectionId))->describe($name, $description);
        $this->transaction->commit();
    }
}
