<?php

declare(strict_types=1);

namespace App\Application\Design\SaveCollection;

use App\Application\Transaction;
use App\Application\WorkspaceContext;
use App\Domain\Design\DesignCollection;
use App\Domain\Design\DesignCollectionRepository;
use Symfony\Component\Uid\Ulid;

final readonly class SaveCollectionHandler
{
    public function __construct(
        private DesignCollectionRepository $collections,
        private WorkspaceContext $workspace,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(?string $collectionId, string $name, ?string $description): Ulid
    {
        if (null === $collectionId) {
            $collection = DesignCollection::start($this->workspace->current(), $name, $description);
            $this->collections->add($collection);
        } else {
            $collection = $this->collections->get(Ulid::fromString($collectionId));
            $collection->describe($name, $description);
        }

        $this->transaction->commit();

        return $collection->id();
    }
}
