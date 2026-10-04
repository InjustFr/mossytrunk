<?php

declare(strict_types=1);

namespace App\Application\Integration\ReadCatalogue;

use App\Application\Integration\AddedConnection;
use App\Application\Integration\ConnectionSession;
use App\Application\Integration\Connectors;
use App\Application\Integration\ImportCatalogue\CatalogueImportReport;
use App\Application\Integration\ImportCatalogue\CatalogueLinker;

final readonly class ReadCatalogueHandler
{
    public function __construct(
        private Connectors $connectors,
        private AddedConnection $addedConnections,
        private ConnectionSession $session,
        private CatalogueLinker $linker,
    ) {
    }

    public function __invoke(string $service): CatalogueImportReport
    {
        $connector = $this->connectors->reading($service);
        $description = $connector->describe();
        $connection = $this->addedConnections->of($connector);

        return $this->linker->link($connection, $description, $connector->catalogueLines($this->session->credentials($connection)));
    }
}
