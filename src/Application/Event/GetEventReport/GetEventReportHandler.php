<?php

declare(strict_types=1);

namespace App\Application\Event\GetEventReport;

use App\Domain\Event\EventRepository;
use App\Domain\Order\OrderRepository;
use App\Domain\Product\Product;
use App\Domain\Product\ProductRepository;
use App\Domain\Reporting\ProductSales;
use App\Domain\Reporting\EventResult;
use Symfony\Component\Uid\Ulid;

final readonly class GetEventReportHandler
{
    public function __construct(
        private EventRepository $events,
        private OrderRepository $orders,
        private ProductRepository $products,
    ) {
    }

    public function __invoke(string $eventId): EventReportView
    {
        $event = $this->events->get(Ulid::fromString($eventId));

        $result = EventResult::of($event, $this->orders->list($event->id()));

        $products = [];
        foreach ($this->products->findByIds(array_map(static fn (ProductSales $sales): Ulid => $sales->productId, $result->productSales)) as $product) {
            /** @var Product $product */
            $products[$product->id()->toRfc4122()] = $product;
        }

        return EventReportView::of($event, $result, $products);
    }
}
