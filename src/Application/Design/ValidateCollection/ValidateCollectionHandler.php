<?php

declare(strict_types=1);

namespace App\Application\Design\ValidateCollection;

use App\Application\Design\DesignProduction;
use App\Application\Transaction;
use App\Domain\Design\DesignRepository;
use Symfony\Component\Uid\Ulid;

final readonly class ValidateCollectionHandler
{
    public function __construct(
        private DesignRepository $designs,
        private DesignProduction $production,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $collectionId): int
    {
        $created = 0;
        foreach ($this->designs->inCollection(Ulid::fromString($collectionId)) as $design) {
            if ([] !== $design->pendingDeclinations()) {
                $created += $this->production->produce($design);
            }
        }
        $this->transaction->commit();

        return $created;
    }
}
