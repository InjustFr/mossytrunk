<?php

declare(strict_types=1);

namespace App\Application\Integration\ImportSales;

use App\Application\Integration\LinePrices;
use App\Application\Product\CreateProductType\MiscellaneousType;
use App\Application\Product\CreateProductType\ProductTypeCreator;
use App\Application\Product\NewProducts;
use App\Application\Translator;
use App\Domain\Integration\ExternalItemRepository;
use App\Domain\Integration\ServiceConnection;
use App\Domain\Product\ProductRepository;
use App\Domain\Product\ProductTypeRepository;
use App\Domain\Sales\SalesChannelRepository;
use Psr\Clock\ClockInterface;

final readonly class ExternalItemResolution
{
    public function __construct(
        private ExternalItemRepository $items,
        private ProductRepository $products,
        private NewProducts $newProducts,
        private ProductTypeRepository $types,
        private ProductTypeCreator $typeCreator,
        private MiscellaneousType $miscellaneous,
        private ClockInterface $clock,
        private Translator $translator,
        private SalesChannelRepository $channels,
    ) {
    }

    public function catalogue(): ImportedCatalogue
    {
        return new ImportedCatalogue($this->products, $this->newProducts, $this->types, $this->typeCreator, $this->miscellaneous);
    }

    public function resolver(ImportedCatalogue $catalogue, ServiceConnection $connection, LinePrices $linePrices): ExternalItemResolver
    {
        return new ExternalItemResolver($catalogue, $this->items, $connection, $linePrices, $this->clock->now(), $this->translator->trans('import.unknown_product'), $this->channels->linkedTo($connection->service()));
    }
}
