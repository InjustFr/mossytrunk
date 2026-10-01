<?php

declare(strict_types=1);

namespace App\Application\Integration\ImportCatalogue;

use App\Application\Integration\Connectors;
use App\Application\Integration\Exception\ServiceNotAdded;
use App\Domain\Integration\ServiceConnectionRepository;

final readonly class ImportCatalogueHandler
{
    public function __construct(
        private Connectors $connectors,
        private ServiceConnectionRepository $connections,
        private CatalogueLinker $linker,
    ) {
    }

    public function __invoke(string $service, string $file): CatalogueImportReport
    {
        $connector = $this->connectors->importing($service);
        $description = $connector->describe();
        $connection = $this->connections->find($service) ?? throw new ServiceNotAdded($description->label);

        return $this->linker->link($connection, $description, $connector->catalogueLines($file));
    }
}
