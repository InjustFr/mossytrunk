<?php

declare(strict_types=1);

namespace App\Application\Event\GetEventReport;

use App\Domain\Event\EventRepository;
use App\Domain\Order\OrderRepository;
use App\Domain\Product\ProductRepository;
use App\Domain\Reporting\EventResult;
use App\Domain\Reporting\ProductSales;
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

        $result = EventResult::of($event, $this->orders->sales($event->id()));

        $products = [];
        foreach ($this->products->findByIds(ProductSales::productIds($result->productSales)) as $product) {
            $products[$product->id()->toRfc4122()] = $product;
        }

        return EventReportView::of($event, $result, $products);
    }
}
