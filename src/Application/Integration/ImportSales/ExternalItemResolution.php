<?php

declare(strict_types=1);

namespace App\Application\Integration\ImportSales;

use App\Application\Integration\LinePrices;
use App\Application\Product\CreateProductType\MiscellaneousType;
use App\Application\Product\CreateProductType\ProductTypeCreator;
use App\Application\Translator;
use App\Domain\Integration\ExternalItemRepository;
use App\Domain\Integration\ServiceConnection;
use App\Domain\Product\ProductReferenceGenerator;
use App\Domain\Product\ProductRepository;
use App\Domain\Product\ProductTypeRepository;
use Psr\Clock\ClockInterface;

final readonly class ExternalItemResolution
{
    public function __construct(
        private ExternalItemRepository $items,
        private ProductRepository $products,
        private ProductReferenceGenerator $references,
        private ProductTypeRepository $types,
        private ProductTypeCreator $typeCreator,
        private MiscellaneousType $miscellaneous,
        private ClockInterface $clock,
        private Translator $translator,
    ) {
    }

    public function catalogueOf(ServiceConnection $connection): ImportedCatalogue
    {
        return new ImportedCatalogue($this->products, $this->references, $this->types, $this->typeCreator, $this->miscellaneous, $connection->workspace());
    }

    public function resolver(ImportedCatalogue $catalogue, ServiceConnection $connection, LinePrices $linePrices): ExternalItemResolver
    {
        return new ExternalItemResolver($catalogue, $this->items, $connection, $linePrices, $this->clock->now(), $this->translator->trans('import.unknown_product'));
    }
}
