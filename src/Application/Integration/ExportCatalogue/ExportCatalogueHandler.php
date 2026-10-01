<?php

declare(strict_types=1);

namespace App\Application\Integration\ExportCatalogue;

use App\Application\Integration\CatalogueItem;
use App\Application\Integration\Connectors;
use App\Application\Translator;
use App\Domain\Product\Product;
use App\Domain\Product\ProductRepository;
use App\Domain\Sales\SalesChannelRepository;
use App\Domain\Shared\DateRange;
use Psr\Clock\ClockInterface;

final readonly class ExportCatalogueHandler
{
    public function __construct(
        private Connectors $connectors,
        private ProductRepository $products,
        private Translator $translator,
        private ClockInterface $clock,
        private SalesChannelRepository $channels,
    ) {
    }

    public function __invoke(string $service): CatalogueFile
    {
        $connector = $this->connectors->exporting($service);
        $channel = $this->channels->linkedTo($service);
        $items = array_map(
            static fn (Product $product): CatalogueItem => new CatalogueItem($product->displayName(), $product->type()->name(), $product->reference(), $product->priceOn($channel), $product->activeVariants()),
            array_values(array_filter($this->products->all(), static fn (Product $product): bool => !$product->isArchived())),
        );

        return new CatalogueFile(
            $this->translator->trans('export.catalogue.filename', [
                'service' => $service,
                'date' => $this->clock->now()->setTimezone(new \DateTimeZone(DateRange::TIMEZONE))->format('Y-m-d'),
            ]),
            $connector->catalogue($items),
            \count($items),
        );
    }
}
