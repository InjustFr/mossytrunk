<?php

declare(strict_types=1);

namespace App\Application\Product\RestoreProduct;

use App\Application\Transaction;
use App\Domain\Product\ProductRepository;
use Symfony\Component\Uid\Ulid;

final readonly class RestoreProductHandler
{
    public function __construct(
        private ProductRepository $products,
        private Transaction $transaction,
    ) {
    }

    public function __invoke(string $productId): void
    {
        $this->products->get(Ulid::fromString($productId))->restore();
        $this->transaction->commit();
    }
}
