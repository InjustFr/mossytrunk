<?php

declare(strict_types=1);

namespace App\Application\Product\ArchiveProduct;

use App\Application\Transaction;
use App\Domain\Product\ProductRepository;
use Psr\Clock\ClockInterface;
use Symfony\Component\Uid\Ulid;

final readonly class ArchiveProductHandler
{
    public function __construct(
        private ProductRepository $products,
        private ClockInterface $clock,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $productId): void
    {
        $this->products->get(Ulid::fromString($productId))->archive($this->clock->now());
        $this->transaction->commit();
    }
}
