<?php

declare(strict_types=1);

namespace App\Application\Etsy\ImportFromEtsy;

use App\Application\Etsy\EtsyGateway;
use App\Application\Etsy\EtsyItemResolver;
use App\Application\Etsy\EtsyReceipt;
use App\Application\Etsy\EtsySession;
use App\Application\Etsy\EtsyUnavailable;
use App\Application\Stock\StockKeeper;
use App\Application\Transaction;
use App\Application\WorkspaceContext;
use App\Domain\Etsy\EtsyListing;
use App\Domain\Etsy\EtsyListingRepository;
use App\Domain\Identity\WorkspaceRepository;
use App\Domain\Order\Order;
use App\Domain\Order\OrderedItem;
use App\Domain\Order\OrderRepository;
use App\Domain\Product\ProductRepository;
use Psr\Clock\ClockInterface;

final readonly class ImportFromEtsyHandler
{
    public function __construct(
        private EtsyGateway $etsy,
        private EtsySession $session,
        private WorkspaceContext $workspace,
        private WorkspaceRepository $workspaces,
        private OrderRepository $orders,
        private ProductRepository $products,
        private EtsyListingRepository $listings,
        private StockKeeper $stock,
        private ClockInterface $clock,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(): EtsyImportReport
    {
        $workspace = $this->workspaces->get($this->workspace->current()->id());
        $shopId = $workspace->etsyShopId() ?? throw EtsyUnavailable::notConnected();
        $receipts = [...$this->etsy->paidReceipts($this->session->app($workspace), $this->session->accessToken($workspace), $shopId)];
        usort($receipts, static fn (EtsyReceipt $a, EtsyReceipt $b): int => $a->createdAt <=> $b->createdAt);

        $alreadyImported = array_flip($this->orders->importedEtsyReceiptIds(array_map(static fn (EtsyReceipt $receipt): string => $receipt->receiptId, $receipts)));
        $resolver = new EtsyItemResolver($this->products, $this->listings, $workspace, $this->clock->now());
        $imported = $waiting = 0;

        foreach ($receipts as $receipt) {
            if (isset($alreadyImported[$receipt->receiptId])) {
                continue;
            }

            $items = [];
            foreach ($receipt->lines as $line) {
                $item = $resolver->resolve($line);
                if (null !== $item) {
                    $items[] = new OrderedItem($item, $line->quantity);
                }
            }
            if (\count($items) !== \count($receipt->lines) || [] === $items) {
                ++$waiting;
                continue;
            }

            $this->orders->add(Order::importFromEtsy($workspace, $receipt->receiptId, $receipt->createdAt, $this->stock->withdraw(null, $items), $receipt->discount, $receipt->shipping));
            ++$imported;
        }

        $this->transaction->commit();

        return new EtsyImportReport(
            $imported,
            \count($alreadyImported),
            $waiting,
            \count(array_filter($this->listings->all(), static fn (EtsyListing $listing): bool => !$listing->isLinked())),
        );
    }
}
