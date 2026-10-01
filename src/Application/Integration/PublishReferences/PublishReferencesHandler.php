<?php

declare(strict_types=1);

namespace App\Application\Integration\PublishReferences;

use App\Application\Integration\ConnectionSession;
use App\Application\Integration\Connectors;
use App\Application\Integration\Exception\ServiceNotAdded;
use App\Application\Integration\ItemReference;
use App\Domain\Integration\ExternalItem;
use App\Domain\Integration\ExternalItemRepository;
use App\Domain\Integration\ServiceConnectionRepository;
use App\Domain\Product\Product;
use App\Domain\Product\ProductRepository;
use Symfony\Component\Uid\Ulid;

final readonly class PublishReferencesHandler
{
    public function __construct(
        private Connectors $connectors,
        private ServiceConnectionRepository $connections,
        private ConnectionSession $session,
        private ExternalItemRepository $items,
        private ProductRepository $products,
    ) {
    }

    public function __invoke(string $service): PublishedReferences
    {
        $connector = $this->connectors->publishing($service);
        $description = $connector->describe();
        $connection = $this->connections->find($service) ?? throw new ServiceNotAdded($description->label);

        $linked = array_values(array_filter($this->items->ofService($service), static fn (ExternalItem $item): bool => null !== $item->productId()));
        $products = [];
        foreach ($this->products->findByIds(array_values(array_filter(array_map(static fn (ExternalItem $item): ?Ulid => $item->productId(), $linked)))) as $product) {
            $products[(string) $product->id()] = $product;
        }

        $references = [];
        foreach ($linked as $item) {
            $product = $products[(string) $item->productId()] ?? null;
            if ($product instanceof Product) {
                $references[] = new ItemReference($item->externalRef(), $item->variation(), null === $item->variant() ? $product->reference() : $product->reference().'-'.$item->variant());
            }
        }

        return new PublishedReferences($description->label, \count($references), $connector->publishReferences($this->session->credentials($connection), $references));
    }
}
