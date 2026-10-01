<?php

declare(strict_types=1);

namespace App\Application\Integration\ReadCatalogue;

use App\Application\Integration\ConnectionSession;
use App\Application\Integration\Connectors;
use App\Application\Integration\Exception\ServiceNotAdded;
use App\Application\Integration\ImportCatalogue\CatalogueImportReport;
use App\Application\Integration\ImportCatalogue\CatalogueLinker;
use App\Domain\Integration\ServiceConnectionRepository;

final readonly class ReadCatalogueHandler
{
    public function __construct(
        private Connectors $connectors,
        private ServiceConnectionRepository $connections,
        private ConnectionSession $session,
        private CatalogueLinker $linker,
    ) {
    }

    public function __invoke(string $service): CatalogueImportReport
    {
        $connector = $this->connectors->reading($service);
        $description = $connector->describe();
        $connection = $this->connections->find($service) ?? throw new ServiceNotAdded($description->label);

        return $this->linker->link($connection, $description, $connector->catalogueLines($this->session->credentials($connection)));
    }
}
