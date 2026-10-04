<?php

declare(strict_types=1);

namespace App\Application\Design\CreateCollection;

use App\Application\Transaction;
use App\Application\WorkspaceContext;
use App\Domain\Design\DesignCollection;
use App\Domain\Design\DesignCollectionRepository;
use Symfony\Component\Uid\Ulid;

final readonly class CreateCollectionHandler
{
    public function __construct(
        private DesignCollectionRepository $collections,
        private WorkspaceContext $workspace,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $name, ?string $description): Ulid
    {
        $collection = DesignCollection::start($this->workspace->current(), $name, $description);
        $this->collections->add($collection);
        $this->transaction->commit();

        return $collection->id();
    }
}
