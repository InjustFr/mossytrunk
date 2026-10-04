<?php

declare(strict_types=1);

namespace App\Application\Integration\ImportCatalogue;

use App\Application\Integration\AddedConnection;
use App\Application\Integration\Connectors;

final readonly class ImportCatalogueHandler
{
    public function __construct(
        private Connectors $connectors,
        private AddedConnection $addedConnections,
        private CatalogueLinker $linker,
    ) {
    }

    public function __invoke(string $service, string $file): CatalogueImportReport
    {
        $connector = $this->connectors->importing($service);
        $description = $connector->describe();
        $connection = $this->addedConnections->of($connector);

        return $this->linker->link($connection, $description, $connector->catalogueLines($file));
    }
}
