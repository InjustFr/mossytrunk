<?php

declare(strict_types=1);

namespace App\Application\Design\UpdateDesign;

use App\Application\Transaction;
use App\Domain\Design\DesignCollectionRepository;
use App\Domain\Design\DesignRepository;
use Symfony\Component\Uid\Ulid;

final readonly class UpdateDesignHandler
{
    public function __construct(
        private DesignRepository $designs,
        private DesignCollectionRepository $collections,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(UpdateDesign $command): void
    {
        $collection = null === $command->collectionId ? null : $this->collections->get(Ulid::fromString($command->collectionId));

        $this->designs->get(Ulid::fromString($command->designId))->describe($command->name, $collection, $command->notes);
        $this->transaction->commit();
    }
}
