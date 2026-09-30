<?php

declare(strict_types=1);

namespace App\Application\Design\DeleteCollection;

use App\Application\Transaction;
use App\Domain\Design\DesignCollectionRepository;
use App\Domain\Design\DesignRepository;
use Symfony\Component\Uid\Ulid;

final readonly class DeleteCollectionHandler
{
    public function __construct(
        private DesignCollectionRepository $collections,
        private DesignRepository $designs,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $collectionId): void
    {
        $collection = $this->collections->get(Ulid::fromString($collectionId));

        foreach ($this->designs->inCollection($collection->id()) as $design) {
            $design->leaveCollection();
        }
        $this->collections->remove($collection);
        $this->transaction->commit();
    }
}
