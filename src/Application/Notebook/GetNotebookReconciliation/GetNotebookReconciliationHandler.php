<?php

declare(strict_types=1);

namespace App\Application\Notebook\GetNotebookReconciliation;

use App\Application\Notebook\RecordedOrders;
use App\Domain\Event\EventRepository;
use App\Domain\Notebook\NotebookScanRepository;
use Symfony\Component\Uid\Ulid;

final readonly class GetNotebookReconciliationHandler
{
    public function __construct(
        private EventRepository $events,
        private NotebookScanRepository $scans,
        private RecordedOrders $orders,
    ) {
    }

    public function __invoke(string $eventId): ?NotebookReconciliationView
    {
        $event = $this->events->get(Ulid::fromString($eventId));
        $scan = $this->scans->ofEvent($event->id());
        if (null === $scan) {
            return null;
        }

        $orders = $this->orders->ofEvent($event->id());

        return NotebookReconciliationView::of($scan, $orders, $scan->reconcile($orders));
    }
}
