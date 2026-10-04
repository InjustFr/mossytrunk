<?php

declare(strict_types=1);

namespace App\Application\Integration\ImportCatalogue;

use App\Application\Integration\ExternalLine;
use App\Application\Integration\ImportSales\ExternalItemResolution;
use App\Application\Integration\ServiceDescription;
use App\Application\Transaction;
use App\Domain\Integration\ServiceConnection;

final readonly class CatalogueLinker
{
    public function __construct(
        private ExternalItemResolution $resolution,
        private Transaction $transaction,
    ) {
    }

    /**
     * @param iterable<ExternalLine> $lines
     */
    public function link(ServiceConnection $connection, ServiceDescription $description, iterable $lines): CatalogueImportReport
    {
        $catalogue = $this->resolution->catalogue();
        $resolver = $this->resolution->resolver($catalogue, $connection, $description->linePrices);
        $read = $linked = 0;
        foreach ($lines as $line) {
            ++$read;
            if (null !== $resolver->resolve($line)) {
                ++$linked;
            }
        }

        $this->transaction->commit();

        return new CatalogueImportReport($description->key, $description->label, $read, $linked, $catalogue->createdCount(), $resolver->itemsToLink());
    }
}
