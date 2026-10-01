<?php

declare(strict_types=1);

namespace App\Application\Integration\ImportCatalogue;

use App\Application\Integration\Connectors;
use App\Application\Integration\Exception\ServiceNotAdded;
use App\Application\Integration\ImportSales\ExternalItemResolution;
use App\Application\Transaction;
use App\Domain\Integration\ServiceConnectionRepository;

final readonly class ImportCatalogueHandler
{
    public function __construct(
        private Connectors $connectors,
        private ServiceConnectionRepository $connections,
        private ExternalItemResolution $resolution,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $service, string $file): CatalogueImportReport
    {
        $connector = $this->connectors->importing($service);
        $description = $connector->describe();
        $connection = $this->connections->find($service) ?? throw new ServiceNotAdded($description->label);
        $lines = $connector->catalogueLines($file);

        $catalogue = $this->resolution->catalogueOf($connection);
        $resolver = $this->resolution->resolver($catalogue, $connection, $description->linePrices);
        $linked = 0;
        foreach ($lines as $line) {
            if (null !== $resolver->resolve($line)) {
                ++$linked;
            }
        }

        $this->transaction->commit();

        return new CatalogueImportReport($service, $description->label, \count($lines), $linked, $catalogue->createdCount(), $resolver->itemsToLink());
    }
}
